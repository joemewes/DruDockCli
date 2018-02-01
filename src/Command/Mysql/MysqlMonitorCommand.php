<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\MysqlMonitorCommand.
 */

namespace Docker\Drupal\Command\Mysql;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class MysqlMonitorCommand
 *
 * @package Docker\Drupal\Command\Mysql
 */
class MysqlMonitorCommand extends Command {

  protected function configure() {
    $this
      ->setName('mysql:log')
      ->setDescription('Monitor mysql activity')
      ->setHelp("This command will output MySQL activity.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);

    $io->section("MySQL ::: Monitor");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }
    else {
      $appname = 'app';
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . 'logs -f mysql';
      $application->runcommand($command, $io);
    }
  }

}
