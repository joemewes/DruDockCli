<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Mysql;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;

/**
 * Class MysqlImportExportCommand
 * @package Docker\Drupal\Command\Mysql
 */
class MysqlImportCommand extends Command {

  const QUESTION = 'question';

  protected function configure() {
    $this
      ->setName('mysql:import')
      ->setDescription('Import .sql files')
      ->setHelp("Use this to import .sql files to the current running APPs drudock_db. [drudock mysql:import -p ./latest.sql]")
      ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Specify import file path including filename');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);

    $io->section("MYSQL ::: import database");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    $helper = $this->getHelper('question');

    $io->warning("Dropping the database is potentially a very bad thing to do.\nAny data stored in the database will be destroyed.");

    $question = new ConfirmationQuestion('Do you really want to drop the \'drudock_db\' database [y/N] : ', FALSE);
    if (!$helper->ask($input, $output, $question)) {
      return;
    }

    // Get import .sql path.
    $path = $input->getOption('path');
    $importpath = null;
    if (!$path) {

      $finder = new Finder();
      $sqlFilter = function (\SplFileInfo $file) {
        return (substr($file, -4) === '.sql') ;
      };

      $finder->files()->in('_mysql_backups')->filter($sqlFilter);
      $sqlDumpsNames = [];
      $sqlDumpsPaths = [];

      $i = 0;
      foreach ($finder as $file) {
        $sqlDumpsNames[$i] = $file->getRelativePathname();
        $sqlDumpsPaths[$i] = $file->getRealPath();
        $i++;
      }

      if ($sqlDumpsNames) {
        $io->info(' ');
        $io->title("SET MYSQL IMPORT");
        $helper = $this->getHelper(self::QUESTION);
        $question = new ChoiceQuestion(
          'Select your import: ',
          $sqlDumpsNames,
          '0,1'
        );
        $question->setMultiselect(TRUE);
        $mysqlChoice = $helper->ask($input, $output, $question);


        foreach($sqlDumpsPaths as $key => $value){
          if(strrpos($value,$mysqlChoice[0])){
            $importpath = $value;
          }
        }

      } else {
        // specify import path
        $helper = $this->getHelper('question');
        $question = new Question('Specify import path, including filename : ');
        $importpath = $helper->ask($input, $output, $question);
      }
    } else {
      $importpath = $path;
    }

    if (file_exists($importpath)) {
      if ($container_application->checkForAppContainers($appname, $io)) {

        $command = $container_application->getComposePath($appname, $io) . 'exec -T mysql mysql -u drudock -pMYSQLPASS -Bse "drop database drudock_db;"';
        $application->runcommand($command, $io);

        // recreate dev_db
        $command = $container_application->getComposePath($appname, $io) . 'exec -T mysql mysql -u drudock -pMYSQLPASS -Bse "create database drudock_db;"';
        $application->runcommand($command, $io);

        // import new .sql file
        // @todo resolve and update - https://github.com/docker/compose/issues/4290
        //$command = $container_application->getComposePath($appname, $io) . 'exec -T mysql mysql -u drudock -pMYSQLPASS drudock_db < ' . $importpath;
        $command = 'docker exec -i $(' . $container_application->getComposePath($appname, $io) . 'ps -q mysql) mysql -u drudock -pMYSQLPASS drudock_db < ' . $importpath;
        $application->runcommand($command, $io, FALSE, 3600, 600);
      }
    }
    else {
      $io->error('Import .sql file not found at path ' . $importpath);
      exit;
    }
  }
}
