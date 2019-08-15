<?php

namespace NblSpreadsheet;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Helper\ProgressBar;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportPopulationCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'spreadsheet:import-population';
    protected $fileDescriptor = [];

    public function __construct()
    {
        $dotenv = new DotEnv();
        $dotenv->load(__DIR__ . '/../.env');
        parent::__construct();
    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Importation population régionale/départementale from xls file (insee)')

            // configure an argument
            ->addArgument('type', InputArgument::REQUIRED, 'type population (regionale ou departementale)')
            ->addOption('age', null, InputOption::VALUE_REQUIRED, 'Mode aggrégation des ages', 'classe')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Cette commande vous permet d\'importer dans une base de données,
                    les données statistiques de la population française par année/region/sexe/age 
                    depuis un fichier xls (source insee : https://www.insee.fr/fr/statistiques/1893198)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Check if argument type is a valid input
        if (!in_array($input->getArgument('type'), ['regionale', 'departementale'])) {
            $output->writeln('<error>Le type saisi "'.$input->getArgument('type').'" n\'est pas une valeur reconnue ! (saisir regionale ou departementale) </error>');
            exit;
        }

        // Check if option is valid
        if (!in_array($input->getOption('age'), ['classe', 'quinquennal'])) {
            $output->writeln('<error>Option age "'.$input->getOption('age').'" n\'est pas une valeur reconnue ! (saisir classe ou quinquennal) </error>');
            exit;
        }

        $filenameToImport = $input->getArgument('type').'-'.$input->getOption('age').'.xls';
        $table = $input->getArgument('type').'_'.$input->getOption('age');

        // Check if xls file is present for selected type
        if (!file_exists(__DIR__.'/../input/'.$filenameToImport)) {
            $output->writeln('<error>Le fichier "input/'.$filenameToImport.'" semble absent ! </error>');
            exit;
        }

        // Create a new Reader of the type Xls
        $reader = IOFactory::createReader('Xls');
        // Load xls file to a PhpSpreadsheet Object
        $spreadsheet = $reader->load(__DIR__.'/../input/'.$filenameToImport);
                
        $fileDescriptor = $this->getFileDescriptor($input->getArgument('type'), $input->getOption('age'));

        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% (%message%) %elapsed:6s%/%estimated:-6s% %memory:6s%');

        // creates a new progress bar (50 units)
        $progressBar = new ProgressBar($output, $spreadsheet->getSheetCount());
        $progressBar->setFormat('custom');
        // starts and displays the progress bar
        $progressBar->start();

        try {
            // Create a connection to database
            $dbh = new \PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Prepare sql insert query
            $sql = 'INSERT INTO '.$table.' (region, annee, sexe, age, population)
                VALUES (?, ?, ?, ?, ?)';
            $sth = $dbh->prepare($sql);

            // Retrieve all sheet names (sheet name are equal to year, omit the first)
            $sheetNames = $spreadsheet->getSheetNames();
            foreach ($sheetNames as $year) {
                if ($year == 'À savoir') continue;
                
                $progressBar->setMessage('Importation année: '.$year);
                $sheet = $spreadsheet->getSheetByName($year);

                // Manage format xls
                // 
                if ($input->getArgument('type') == 'regionale' and $input->getOption('age') == 'quinquennal' and $year == 1998) {
                    $fileDescriptor['zones'] = [
                        '6' => '84', '7' => '27', '8' => '53', '9' => '24', '10' => '94', '11' => '44',
                        '12' => '32', '13' => '11', '14' => '28', '15' => '75', '16' => '76', '17' => '52',
                        '18' => '93', '23' => '01', '24' => '02', '25' => '03', '26' => '04'
                    ];
                }

                if ($input->getArgument('type') == 'departementale' and $input->getOption('age') == 'quinquennal' and $year == 1998) {
                    $fileDescriptor['zones'] = [
                        '6' => '01', '7' => '02', '8' => '03', '9' => '04', '10' => '05', '11' => '06', '12' => '07',
                        '13' => '08', '14' => '09', '15' => '10', '16' => '11', '17' => '12', '18' => '13', '19' => '14',
                        '20' => '15', '21' => '16', '22' => '17', '23' => '18', '24' => '19', '25' => '2A', '26' => '2B',
                        '27' => '21', '28' => '22', '29' => '23', '30' => '24', '31' => '25', '32' => '26', '33' => '27',
                        '34' => '28', '35' => '29', '36' => '30', '37' => '31', '38' => '32', '39' => '33', '40' => '34',
                        '41' => '35', '42' => '36', '43' => '37', '44' => '38', '45' => '39', '46' => '40', '47' => '41',
                        '48' => '42', '49' => '43', '50' => '44', '51' => '45', '52' => '46', '53' => '47', '54' => '48',
                        '55' => '49', '56' => '50', '57' => '51', '58' => '52', '59' => '53', '60' => '54', '61' => '55',
                        '62' => '56', '63' => '57', '64' => '58', '65' => '59', '66' => '60', '67' => '61', '68' => '62',
                        '69' => '63', '70' => '64', '71' => '65', '72' => '66', '73' => '67', '74' => '68', '75' => '69',
                        '76' => '70', '77' => '71', '78' => '72', '79' => '73', '80' => '74', '81' => '75', '82' => '76',
                        '83' => '77', '84' => '78', '85' => '79', '86' => '80', '87' => '81', '88' => '82', '89' => '83',
                        '90' => '84', '91' => '85', '92' => '86', '93' => '87', '94' => '88', '95' => '89', '96' => '90',
                        '97' => '91', '98' => '92', '99' => '93', '100' => '94', '101' => '95', '106' => '971', '107' => '972',
                        '108' => '973', '109' => '974'
                    ];
                }

                // Iterate row on regions
                foreach ($fileDescriptor['zones'] as $regionRow => $regionInsee) {
                    if ($input->getArgument('type') == 'regionale' and $year <= 2013 and $year > 1998 and $regionRow == 24) continue; // Mayotte présent à partir de 2014 (warning bug file regionale/classe année 1998 format !!!)
                    if ($input->getArgument('type') == 'departementale' and $year <= 2013 and $year > 1998 and $regionRow == 107) continue; // Mayotte présent à partir de 2014
                    if ($input->getArgument('type') == 'regionale' and $year < 1990 and $regionRow > 18) continue; // Pas de data pour les DOM avant 1990
                    if ($input->getArgument('type') == 'departementale' and $year < 1990 and $regionRow > 101) continue; // Pas de data pour les DOM avant 1990

                    // Iterate column for men (2) /age
                    foreach ($fileDescriptor['homme'] as $menColumn => $menAge) {
                        $sth->execute([
                            $regionInsee,
                            $year,
                            2,
                            $menAge,
                            intval($sheet->getCell($menColumn.$regionRow)->getValue()),
                        ]);
                    }
                    
                    // Iterate column for women (1) /age
                    foreach ($fileDescriptor['femme'] as $womenColumn => $womenAge) {
                        $sth->execute([
                            $regionInsee,
                            $year,
                            1,
                            $womenAge,
                            intval($sheet->getCell($womenColumn.$regionRow)->getValue()),
                        ]);
                    }
                }

                // advances the progress bar 1 unit
                $progressBar->advance();
            }
        } catch (\PDOException $e) {
            $output->writeln('<error>PDO : '.$e->getMessage().'</error>');
        } 

        // ensures that the progress bar is at 100%
        $progressBar->finish();
        echo "\n";
    } 

    private function getFileDescriptor($type, $age) {
        $fileDescriptor = [];

        if ($type == 'regionale') {
            $fileDescriptor['zones'] = [
                '6' => '84', '7' => '27', '8' => '53', '9' => '24', '10' => '94', '11' => '44',
                '12' => '32', '13' => '11', '14' => '28', '15' => '75', '16' => '76', '17' => '52',
                '18' => '93', '20' => '01', '21' => '02', '22' => '03', '23' => '04', '24' => '06',
            ];

            if ($age == 'classe') {
                $fileDescriptor['homme'] = [
                    'H' => 1, // [0 - 19]
                    'I' => 2, // [20 - 39]
                    'J' => 3, // [40 - 59]
                    'K' => 4, // [60 - 74]
                    'L' => 5, // [75 - ++]
                ];

                $fileDescriptor['femme'] = [
                    'N' => 1, // [0 - 19]
                    'O' => 2, // [20 - 39]
                    'P' => 3, // [40 - 59]
                    'Q' => 4, // [60 - 74]
                    'R' => 5, // [75 - ++]
                ];
            } else {
                $fileDescriptor['homme'] = [
                    'W' => 1, // [0 - 4] 
                    'X' => 2, // [5 - 9]
                    'Y' => 3, // [10 - 14]
                    'Z' => 4, // [15 - 19]
                    'AA' => 5, // [20 - 24]
                    'AB' => 6, // [25 - 29]
                    'AC' => 7, // [30 - 34]
                    'AD' => 8, // [35 - 39]
                    'AE' => 9, // [40 - 44]
                    'AF' => 10, // [45 - 49]
                    'AG' => 11, // [50 - 54]
                    'AH' => 12, // [55 - 59]
                    'AI' => 13, // [60 - 64]
                    'AJ' => 14, // [65 - 69]
                    'AK' => 15, // [70 - 74]
                    'AL' => 16, // [75 - 79]
                    'AM' => 17, // [80 - 84]
                    'AN' => 18, // [85 - 89]
                    'AO' => 19, // [90 - 94]
                    'AP' => 20, // [95 - +]
                ];

                $fileDescriptor['femme'] = [
                    'AR' => 1, // [0 - 4] 
                    'AS' => 2, // [5 - 9]
                    'AT' => 3, // [10 - 14]
                    'AU' => 4, // [15 - 19]
                    'AV' => 5, // [20 - 24]
                    'AW' => 6, // [25 - 29]
                    'AX' => 7, // [30 - 34]
                    'AY' => 8, // [35 - 39]
                    'AZ' => 9, // [40 - 44]
                    'BA' => 10, // [45 - 49]
                    'BB' => 11, // [50 - 54]
                    'BC' => 12, // [55 - 59]
                    'BD' => 13, // [60 - 64]
                    'BE' => 14, // [65 - 69]
                    'BF' => 15, // [70 - 74]
                    'BG' => 16, // [75 - 79]
                    'BH' => 17, // [80 - 84]
                    'BI' => 18, // [85 - 89]
                    'BJ' => 19, // [90 - 94]
                    'BK' => 20, // [95 - +]
                ];
            }
        } else {
            $fileDescriptor['zones'] = [
                '6' => '01', '7' => '02', '8' => '03', '9' => '04', '10' => '05', '11' => '06', '12' => '07',
                '13' => '08', '14' => '09', '15' => '10', '16' => '11', '17' => '12', '18' => '13', '19' => '14',
                '20' => '15', '21' => '16', '22' => '17', '23' => '18', '24' => '19', '25' => '2A', '26' => '2B',
                '27' => '21', '28' => '22', '29' => '23', '30' => '24', '31' => '25', '32' => '26', '33' => '27',
                '34' => '28', '35' => '29', '36' => '30', '37' => '31', '38' => '32', '39' => '33', '40' => '34',
                '41' => '35', '42' => '36', '43' => '37', '44' => '38', '45' => '39', '46' => '40', '47' => '41',
                '48' => '42', '49' => '43', '50' => '44', '51' => '45', '52' => '46', '53' => '47', '54' => '48',
                '55' => '49', '56' => '50', '57' => '51', '58' => '52', '59' => '53', '60' => '54', '61' => '55',
                '62' => '56', '63' => '57', '64' => '58', '65' => '59', '66' => '60', '67' => '61', '68' => '62',
                '69' => '63', '70' => '64', '71' => '65', '72' => '66', '73' => '67', '74' => '68', '75' => '69',
                '76' => '70', '77' => '71', '78' => '72', '79' => '73', '80' => '74', '81' => '75', '82' => '76',
                '83' => '77', '84' => '78', '85' => '79', '86' => '80', '87' => '81', '88' => '82', '89' => '83',
                '90' => '84', '91' => '85', '92' => '86', '93' => '87', '94' => '88', '95' => '89', '96' => '90',
                '97' => '91', '98' => '92', '99' => '93', '100' => '94', '101' => '95', '103' => '971', '104' => '972',
                '105' => '973', '106' => '974', '107' => '976', 
             ];

             if ($age == 'classe') {
                $fileDescriptor['homme'] = [
                    'I' => 1, // [0 - 19]
                    'J' => 2, // [20 - 39]
                    'K' => 3, // [40 - 59]
                    'L' => 4, // [60 - 74]
                    'M' => 5, // [75 - ++]
                ];

                $fileDescriptor['femme'] = [
                    'O' => 1, // [0 - 19]
                    'P' => 2, // [20 - 39]
                    'Q' => 3, // [40 - 59]
                    'R' => 4, // [60 - 74]
                    'S' => 5, // [75 - ++]
                ];
            } else {
                $fileDescriptor['homme'] = [
                    'X' => 1, // [0 - 4] 
                    'Y' => 2, // [5 - 9]
                    'Z' => 3, // [10 - 14]
                    'AA' => 4, // [15 - 19]
                    'AB' => 5, // [20 - 24]
                    'AC' => 6, // [25 - 29]
                    'AD' => 7, // [30 - 34]
                    'AE' => 8, // [35 - 39]
                    'AF' => 9, // [40 - 44]
                    'AG' => 10, // [45 - 49]
                    'AH' => 11, // [50 - 54]
                    'AI' => 12, // [55 - 59]
                    'AJ' => 13, // [60 - 64]
                    'AK' => 14, // [65 - 69]
                    'AL' => 15, // [70 - 74]
                    'AM' => 16, // [75 - 79]
                    'AN' => 17, // [80 - 84]
                    'AO' => 18, // [85 - 89]
                    'AP' => 19, // [90 - 94]
                    'AQ' => 20, // [95 - +]
                ];

                $fileDescriptor['femme'] = [
                    'AS' => 1, // [0 - 4] 
                    'AT' => 2, // [5 - 9]
                    'AU' => 3, // [10 - 14]
                    'AV' => 4, // [15 - 19]
                    'AW' => 5, // [20 - 24]
                    'AX' => 6, // [25 - 29]
                    'AY' => 7, // [30 - 34]
                    'AZ' => 8, // [35 - 39]
                    'BA' => 9, // [40 - 44]
                    'BB' => 10, // [45 - 49]
                    'BC' => 11, // [50 - 54]
                    'BD' => 12, // [55 - 59]
                    'BE' => 13, // [60 - 64]
                    'BF' => 14, // [65 - 69]
                    'BG' => 15, // [70 - 74]
                    'BH' => 16, // [75 - 79]
                    'BI' => 17, // [80 - 84]
                    'BJ' => 18, // [85 - 89]
                    'BK' => 19, // [90 - 94]
                    'BL' => 20, // [95 - +]
                ];
            }
        }

        return $fileDescriptor;
    }
}