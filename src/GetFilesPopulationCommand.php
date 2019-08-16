<?php

namespace NblSpreadsheet;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Helper\ProgressBar;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class GetFilesPopulationCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'population:getfiles';

    protected $files = [
        'departementale-classe' => 'https://www.insee.fr/fr/statistiques/fichier/1893198/estim-pop-dep-sexe-gca-1975-2019.xls',
        'departementale-quinquennal' => 'https://www.insee.fr/fr/statistiques/fichier/1893198/estim-pop-dep-sexe-aq-1975-2019.xls',
        'regionale-classe' => 'https://www.insee.fr/fr/statistiques/fichier/1893198/estim-pop-nreg-sexe-gca-1975-2019.xls',
        'regionale-quinquennal' => 'https://www.insee.fr/fr/statistiques/fichier/1893198/estim-pop-nreg-sexe-aq-1975-2019.xls',
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
            ->setDescription('Récupération des fichiers population régionale/départementale depuis le site de l\'Insee.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Cette commande vous permet de récupérer les fichiers de données statistiques de la population française par année/region/sexe/age 
                    depuis un fichier xls (source insee : https://www.insee.fr/fr/statistiques/1893198)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% (%message%) %elapsed:6s%/%estimated:-6s% %memory:6s%');

        // creates a new progress bar (50 units)
        $progressBar = new ProgressBar($output, count($this->files));
        $progressBar->setFormat('custom');
        // starts and displays the progress bar
        $progressBar->start();

        // Retrieve all files on Insee website
        foreach ($this->files as $filename => $file) {
            $progressBar->setMessage('Récupération du fichier: '.$file);
            
            $content = file_get_contents($file);
            file_put_contents(__DIR__.'/../input/'.$filename.'.xls', $content);

            // advances the progress bar 1 unit
            $progressBar->advance();
        } 

        // ensures that the progress bar is at 100%
        $progressBar->finish();
        echo "\n";
    } 
}