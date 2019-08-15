<?php

namespace NblSpreadsheet;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;

class PrepareDatabasePopulationCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'spreadsheet:prepare-database-population';
    protected $dbh = null;

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
            ->setDescription('Préparation d\'une base de donnée locale en vue de l\'import des statistiques de population régionale/départementale de l\'Insee.')

            ->addOption('remove', null, InputOption::VALUE_REQUIRED, 'Supprime les tables et les recréé si existe', false)
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Supprime/Vide la table sélectionnée')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Cette commande vous permet de préparer votre base de données locale.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            // Create a connection to database
            $this->dbh = new \PDO($_ENV['DB_DSN'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Check tables exists
            $requiredTables = ['departementale_classe', 'departementale_quinquennal', 'regionale_classe', 'regionale_quinquennal'];
            // Retrieve all tables in database
            if (substr($_ENV['DB_DSN'], 0, 5) == 'pgsql') {
                $sql = "select table_name from information_schema.tables where table_schema='public'";
            } else {
                $sql = 'SHOW tables';
            }
            
            $sth = $this->dbh->prepare($sql);
            $sth->execute();
            $tables = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
            foreach ($requiredTables as $table) {
                if ($input->hasOption('table') and $table != $input->getOption('table')) continue;

                if (!in_array($table, $tables)) {
                    // Build table
                    $this->createTable($table);

                    $output->writeln('<info>Table : '.$table.' créée.</info>');
                } else {
                    $output->writeln('<info>Table : '.$table.' présente.</info>');

                    if ($input->getOption('remove')) {
                        if (substr($_ENV['DB_DSN'], 0, 5) == 'pgsql') {
                            $sth = $this->dbh->prepare('DROP TABLE '.$table.' CASCADE');
                        } else {
                            $sth = $this->dbh->prepare('DROP TABLE '.$table);
                        }
                        $sth->execute();

                        // Rebuild table
                        $this->createTable($table);
                        $output->writeln('<info>Table : '.$table.' créée.</info>');
                    }

                    $sth = $this->dbh->prepare('TRUNCATE '.$table);
                    $sth->execute();
                    $output->writeln('<info>Table : '.$table.' vidée.</info>');
                }
            }
        } catch (\PDOException $e) {
            $output->writeln('<error>PDO :'.$e->getMessage().'.</error>');
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur :'.$e->getMessage().'.</error>');
        }
    } 

    private function createTable($table) {
        if (substr($_ENV['DB_DSN'], 0, 5) == 'pgsql') {
            // Postgresql
            $this->dbh->exec('
                CREATE TABLE public.'.$table.' (
                    id integer NOT NULL,
                    region character varying(3),
                    annee smallint,
                    sexe smallint,
                    age smallint,
                    population integer
                )
            ');

            $this->dbh->exec('
                ALTER TABLE public.'.$table.' OWNER TO population;
            ');

            $this->dbh->exec('
                CREATE SEQUENCE public.'.$table.'_id_seq
                    AS integer
                    START WITH 1
                    INCREMENT BY 1
                    NO MINVALUE
                    NO MAXVALUE
                    CACHE 1
            ');

            $this->dbh->exec('
                ALTER TABLE public.'.$table.'_id_seq OWNER TO population;
            ');

            $this->dbh->exec('
                ALTER SEQUENCE public.'.$table.'_id_seq OWNED BY public.'.$table.'.id;
            ');

            $this->dbh->exec('
                ALTER TABLE ONLY public.'.$table.' ALTER COLUMN id SET DEFAULT nextval(\'public.'.$table.'_id_seq\'::regclass);
            ');

            $this->dbh->exec('
                SELECT pg_catalog.setval(\'public.'.$table.'_id_seq\', 1, false);
            ');
        } else {
            // MySQL & MariaDB
            // Create table
            $this->dbh->exec('
                CREATE TABLE '.$table.' (
                    `id` int(11) NOT NULL,
                    `region` varchar(3) NOT NULL,
                    `annee` smallint(6) NOT NULL,
                    `sexe` tinyint(4) NOT NULL,
                    `age` tinyint(4) NOT NULL,
                    `population` int(11) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ');

            // Manage index
            $this->dbh->exec('
                ALTER TABLE '.$table.'
                ADD PRIMARY KEY (`id`),
                ADD KEY `region` (`region`),
                ADD KEY `annee` (`annee`)
            ');

            $this->dbh->exec('
                ALTER TABLE '.$table.'
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT
            ');
        }
        
    }
}