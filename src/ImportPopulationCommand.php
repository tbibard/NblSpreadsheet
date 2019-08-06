<?php

namespace NblSpreadsheet;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Helper\ProgressBar;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ImportPopulationCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'spreadsheet:import-population';
    protected $regions = [
        '6' => '84',
        '7' => '27',
        '8' => '53',
        '9' => '24',
        '10' => '94',
        '11' => '44',
        '12' => '32',
        '13' => '11',
        '14' => '28',
        '15' => '75',
        '16' => '76',
        '17' => '52',
        '18' => '93',
    ];
    protected $menParts = [
        'H' => 1, // [0 - 19]
        'I' => 2, // [20 - 39]
        'J' => 3, // [40 - 59]
        'K' => 4, // [60 - 74]
        'L' => 5, // [75 - ++]
    ];
    protected $womenParts = [
        'H' => 1, // [0 - 19]
        'I' => 2, // [20 - 39]
        'J' => 3, // [40 - 59]
        'K' => 4, // [60 - 74]
        'L' => 5, // [75 - ++]
    ];

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
        ->setDescription('Importation population régionale from xls file (insee)')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Cette commande vous permet d\'importer dans une base de données,
                les données statistiques de la population française par année/region/sexe/age 
                depuis un fichier xls (source insee : https://www.insee.fr/fr/statistiques/1893198)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Create a new Reader of the type Xls
        $reader = IOFactory::createReader('Xls');
        // Load xls file to a PhpSpreadsheet Object
        $spreadsheet = $reader->load('estim-pop-nreg-sexe-gca-1975-2019.xls');
                
        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% (%message%) %elapsed:6s%/%estimated:-6s% %memory:6s%');

        // creates a new progress bar (50 units)
        $progressBar = new ProgressBar($output, $spreadsheet->getSheetCount());
        $progressBar->setFormat('custom');
        // starts and displays the progress bar
        $progressBar->start();

        // Create a connection to database
        $dbh = new \PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
        // Prepare sql insert query
        $sql = 'INSERT INTO regions (region, annee, sexe, age, population)
            VALUES (?, ?, ?, ?, ?)';
        $sth = $dbh->prepare($sql);

        // Retrieve all sheet names (sheet name are equal to year, omit the first)
        $sheetNames = $spreadsheet->getSheetNames();
        foreach ($sheetNames as $year) {
            if ($year == 'À savoir') continue;
            
            $progressBar->setMessage('Importation année: '.$year);
            $sheet = $spreadsheet->getSheetByName($year);

            // Iterate row on regions
            foreach ($this->regions as $regionRow => $regionInsee) {
                // Iterate column for men (2) /age
                foreach ($this->menParts as $menColumn => $menAge) {

                    $sth->execute([
                        $regionInsee,
                        $year,
                        2,
                        $menAge,
                        $sheet->getCell($menColumn.$regionRow),
                    ]);
                }
                
                // Iterate column for women (1) /age
                foreach ($this->womenParts as $womenColumn => $womenAge) {

                    $sth->execute([
                        $regionInsee,
                        $year,
                        1,
                        $womenAge,
                        $sheet->getCell($womenColumn.$regionRow),
                    ]);
                }
            }

            // advances the progress bar 1 unit
            $progressBar->advance();
        } 

        // ensures that the progress bar is at 100%
        $progressBar->finish();
        echo "\n";
    } 
}