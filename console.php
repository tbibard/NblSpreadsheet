#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use NblSpreadsheet\ImportPopulationCommand;
use NblSpreadsheet\GetFilesPopulationCommand;
use NblSpreadsheet\PrepareDatabasePopulationCommand;
use NblSpreadsheet\CheckDataPopulationCommand;

$application = new Application('NblSpreadsheet App', '0.0.1');
$application->add(new ImportPopulationCommand());
$application->add(new GetFilesPopulationCommand());
$application->add(new PrepareDatabasePopulationCommand());
$application->add(new CheckDataPopulationCommand());
$application->run();