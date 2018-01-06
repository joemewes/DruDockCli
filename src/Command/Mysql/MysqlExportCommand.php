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
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class MysqlImportExportCommand
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
      ->setHelp("Use this to dump .sql files to the current running APPs drudock_db. eg. [drudock mysql:export -p ./latest.sql]")
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

    // GET AND SET APP TYPE
    $savepath = $input->getOption('path');

    if (!$savepath) {
      //specify save path
      $datetime = date("Y-m-d--H-i-s");
      $helper = $this->getHelper('question');
      $question = new Question('Specify save path, including filename [latest-' . $appname . '--' . $datetime . '.sql] : ', 'latest-' . $appname . '--' . $datetime . '.sql');
      $savepath = $helper->ask($input, $output, $question);
    }

    $mysqldump = "mysqldump -u drudock -pMYSQLPASS drudock_db";

    if (isset($type) && $type == 'D7') {
      $mysqldump = "(mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --ignore-table=drudock_db.cache \
                    --ignore-table=drudock_db.cache_admin_menu \
                    --ignore-table=drudock_db.cache_block \
                    --ignore-table=drudock_db.cache_bootstrap \
                    --ignore-table=drudock_db.cache_entity_comment \
                    --ignore-table=drudock_db.cache_entity_fieldable_panels_pane \
                    --ignore-table=drudock_db.cache_entity_file \
                    --ignore-table=drudock_db.cache_entity_node \
                    --ignore-table=drudock_db.cache_entity_paragraphs_item \
                    --ignore-table=drudock_db.cache_entity_taxonomy_term \
                    --ignore-table=drudock_db.cache_entity_taxonomy_vocabulary \
                    --ignore-table=drudock_db.cache_entity_user \
                    --ignore-table=drudock_db.cache_entityconnect \
                    --ignore-table=drudock_db.cache_features \
                    --ignore-table=drudock_db.cache_feeds_http \
                    --ignore-table=drudock_db.cache_field \
                    --ignore-table=drudock_db.cache_filter \
                    --ignore-table=drudock_db.cache_form \
                    --ignore-table=drudock_db.cache_image \
                    --ignore-table=drudock_db.cache_libraries \
                    --ignore-table=drudock_db.cache_mailchimp \
                    --ignore-table=drudock_db.cache_menu \
                    --ignore-table=drudock_db.cache_metatag \
                    --ignore-table=drudock_db.cache_oembed \
                    --ignore-table=drudock_db.cache_page \
                    --ignore-table=drudock_db.cache_panels \
                    --ignore-table=drudock_db.cache_path \
                    --ignore-table=drudock_db.cache_rules \
                    --ignore-table=drudock_db.cache_token \
                    --ignore-table=drudock_db.cache_update \
                    --ignore-table=drudock_db.cache_variable \
                    --ignore-table=drudock_db.cache_views \
                    --ignore-table=drudock_db.cache_views_data \
                    --ignore-table=drudock_db.history \
                    --ignore-table=drudock_db.sessions \
                    --ignore-table=drudock_db.watchdog &&
                    mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --no-data cache \
                    --no-data cache_admin_menu \
                    --no-data cache_block \
                    --no-data cache_bootstrap \
                    --no-data cache_entity_comment \
                    --no-data cache_entity_fieldable_panels_pane \
                    --no-data cache_entity_file \
                    --no-data cache_entity_node \
                    --no-data cache_entity_paragraphs_item \
                    --no-data cache_entity_taxonomy_term \
                    --no-data cache_entity_taxonomy_vocabulary \
                    --no-data cache_entity_user \
                    --no-data cache_entityconnect \
                    --no-data cache_features \
                    --no-data cache_feeds_http \
                    --no-data cache_field \
                    --no-data cache_filter \
                    --no-data cache_form \
                    --no-data cache_image \
                    --no-data cache_libraries \
                    --no-data cache_mailchimp \
                    --no-data cache_menu \
                    --no-data cache_metatag \
                    --no-data cache_oembed \
                    --no-data cache_page \
                    --no-data cache_panels \
                    --no-data cache_path \
                    --no-data cache_rules \
                    --no-data cache_token \
                    --no-data cache_update \
                    --no-data cache_variable \
                    --no-data cache_views \
                    --no-data cache_views_data \
                    --no-data history \
                    --no-data sessions \
                    --no-data watchdog \
                    )";
    }

    if (isset($type) && $type == 'D8') {
      $mysqldump = "(mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --ignore-table=drudock_db.cache_bootstrap \
                    --ignore-table=drudock_db.cache_config \
                    --ignore-table=drudock_db.cache_container \
                    --ignore-table=drudock_db.cache_data \
                    --ignore-table=drudock_db.cache_default \
                    --ignore-table=drudock_db.cache_discovery \
                    --ignore-table=drudock_db.cache_entity \
                    --ignore-table=drudock_db.cache_menu \
                    --ignore-table=drudock_db.cache_page \
                    --ignore-table=drudock_db.cachetags && \
                    mysqldump  -u drudock -pMYSQLPASS drudock_db \
                    --no-data cache_bootstrap \
                    --no-data cache_config \
                    --no-data cache_container \
                    --no-data cache_data \
                    --no-data cache_default \
                    --no-data cache_discovery \
                    --no-data cache_entity \
                    --no-data cache_menu \
                    --no-data cache_page \
                    --no-data cachetags \
                    )";
    }

    if ($container_application->checkForAppContainers($appname, $io) && isset($savepath)) {
      $command = $container_application->getComposePath($appname, $io) . 'exec -T mysql bash -c "' . $mysqldump . '" > ' . $savepath;
      $application->runcommand($command, $io);
    }
  }
}
