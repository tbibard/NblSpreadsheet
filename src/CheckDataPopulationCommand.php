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

class CheckDataPopulationCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'population:check-data';
    protected $tables = ['regionale_classe', 'regionale_quinquennal', 'departementale_classe', 'departementale_quinquennal'];
    protected $populationTotaux = [
        2019 => 66992699,
        2018 => 66890699,
        2017 => 66768420,
        2016 => 66602645,
        2015 => 66422469,
        2014 => 66130873,
        2013 => 65564756,
        2012 => 65241241,
        2011 => 64933400,
        2010 => 64612939,
        2009 => 64304500,
        2008 => 63961859,
        2007 => 63600690,
        2006 => 63186117,
        2005 => 62730537,
        2004 => 62251062,
        2003 => 61824030,
        2002 => 61385070,
        2001 => 60941410,
        2000 => 60508150,
        1999 => 60122665,
        1998 => 59899347,
        1997 => 59691177,
        1996 => 59487413,
        1995 => 59280577,
        1994 => 59070077,
        1993 => 58852002,
        1992 => 58571237,
        1991 => 58280135,
        1990 => 57998429,
        1989 => 56269810,
        1988 => 55966142,
        1987 => 55681780,
        1986 => 55411238,
        1985 => 55157303,
        1984 => 54894854,
        1983 => 54649984,
        1982 => 54335000,
        1981 => 54028630,
        1980 => 53731387,
        1979 => 53481073,
        1978 => 53271566,
        1977 => 53019005,
        1976 => 52798338,
        1975 => 52600000,
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
            ->setDescription('Importation population régionale/départementale from xls file (insee)')

            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Vérifier une table précise', 'all')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Cette commande vous permet d\'importer dans une base de données,
                    les données statistiques de la population française par année/region/sexe/age 
                    depuis un fichier xls (source insee : https://www.insee.fr/fr/statistiques/1893198)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Check if option is valid
        if (!in_array($input->getOption('table'), ['all', 'regionale_classe', 'regionale_quinquennal', 'departementale_classe', 'departementale_quinquennal'])) {
            $output->writeln('<error>Option table "'.$input->getOption('table').'" n\'est pas une valeur reconnue ! (saisir [regionale|departementale]_[classe|quinquennal]) </error>');
            exit;
        }

        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% (%message%) %elapsed:6s%/%estimated:-6s% %memory:6s%');

        // creates a new progress bar
        if ($input->hasOption('table') and $input->getOption('table') != 'all') {
            $progressBar = new ProgressBar($output, count($this->populationTotaux));
        } else {
            $progressBar = new ProgressBar($output, count($this->populationTotaux) * 4);
        }
        $progressBar->setFormat('custom');
        // starts and displays the progress bar
        $progressBar->start();

        try {
            // Create a connection to database
            $dbh = new \PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ($this->tables as $table) {
                if ($input->hasOption('table') and $input->getOption('table') != 'all' and $input->getOption('table') != $table) continue;

                $progressBar->setMessage('Check data: '.$table);

                // Prepare sql insert query
                $sql = 'SELECT SUM(population) as total FROM '.$table.' WHERE annee = ?';
                $sth = $dbh->prepare($sql);
            
                foreach ($this->populationTotaux as $annee => $total) {
                    $sth->execute([$annee]);
                    $totalDb = $sth->fetchColumn(0);

                    if ($totalDb != $total) {
                        $output->writeln('<error>Population totale '.$table.' pour année '.$annee.' ne correspond pas (DB='.$totalDb.' != '.$total.') ! </error>');
                    }
                    // advances the progress bar 1 unit
                    $progressBar->advance();
                }
            }
        } catch (\PDOException $e) {
            $output->writeln('<error>PDO : '.$e->getMessage().'</error>');
        } 

        // ensures that the progress bar is at 100%
        $progressBar->finish();
        echo "\n";
    } 
}