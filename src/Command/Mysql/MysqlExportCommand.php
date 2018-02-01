<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\MysqlExportCommand.
 */

namespace Docker\Drupal\Command\Mysql;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class MysqlExportCommand
 *
 * @package Docker\Drupal\Command\Mysql
 */
class MysqlExportCommand extends Command {

  /**
   * Define constants
   */

  // Config constants.
  const APP_NAME = 'appname';

  const APP_TYPE = 'apptype';

  protected function configure() {
    $this
      ->setName('mysql:export')
      ->setDescription('Export .sql files')
      ->setHelp("Use this to dump .sql files to the current running APPs drudock_db. eg. [drudock mysql:export -p ./_mysql_backups/latest.sql]")
      ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Specify export file path including filename [./latest.sql]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);
    $io->section('MYSQL ::: dump/export database');

    if ($config = $application->getAppConfig($io)) {
      $appname = $config[self::APP_NAME];
      $type = $config[self::APP_TYPE];
    }
    else {
      $appname = 'app';
    }

    if (!file_exists('_mysql_backups')) {
      mkdir('_mysql_backups', 0777, true);
    }

    // GET AND SET APP TYPE.
    $savepath = $input->getOption('path');

    if (!$savepath) {
      // Specify save path.
      $datetime = date("Y-m-d--H-i-s");
      $helper = $this->getHelper('question');
      $question = new Question('Specify save path, including filename [_mysql_backups/latest-' . $appname . '--' . $datetime . '.sql] : ', 'latest-' . $appname . '--' . $datetime . '.sql');
      $savepath = $helper->ask($input, $output, $question);
    }

    $mysqldump = "mysqldump -u drudock -pMYSQLPASS drudock_db";

    if (isset($type) && $type == 'D7') {
      $mysqldump = "(mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --ignore-table=drudock_db.cache \
                    --ignore-table=drudock_db.cache_block \
                    --ignore-table=drudock_db.cache_bootstrap \
                    --ignore-table=drudock_db.cache_field \
                    --ignore-table=drudock_db.cache_filter \
                    --ignore-table=drudock_db.cache_form \
                    --ignore-table=drudock_db.cache_menu \
                    --ignore-table=drudock_db.cache_page \
                    --ignore-table=drudock_db.cache_path \
                    --ignore-table=drudock_db.cache_update \
                    --ignore-table=drudock_db.history \
                    --ignore-table=drudock_db.sessions \
                    --ignore-table=drudock_db.watchdog &&
                    mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --no-data cache \
                    --no-data cache_block \
                    --no-data cache_bootstrap \
                    --no-data cache_field \
                    --no-data cache_filter \
                    --no-data cache_form \
                    --no-data cache_menu \
                    --no-data cache_page \
                    --no-data cache_path \
                    --no-data cache_update \
                    --no-data history \
                    --no-data sessions \
                    --no-data watchdog \
                    )";
    }

    if (isset($type) && $type == 'D8') {
      $mysqldump = "(mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --ignore-table=drudock_db.cache_bootstrap \
                    --ignore-table=drudock_db.cache_menu \
                    --ignore-table=drudock_db.cache_page \
                    --ignore-table=drudock_db.cachetags && \
                    mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --no-data cache_bootstrap \
                    --no-data cache_menu \
                    --no-data cache_page \
                    )";
    }

    if ($container_application->checkForAppContainers($appname, $io) && isset($savepath)) {
      $command = $container_application->getComposePath($appname, $io) . 'exec -T mysql bash -c "' . $mysqldump . '" > ./_mysql_backups/' . $savepath;
      $io->info("Exporting database. This may take a few minutes depending on size of import. Please wait.");
      $application->runcommand($command, $io);
    }
  }
}
