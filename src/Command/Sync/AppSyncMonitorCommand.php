<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Sync;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class WatchCommand
 * @package Docker\Drupal\Command\redis
 */
class AppSyncMonitorCommand extends Command {

  protected function configure() {
    $this
      ->setName('sync:monitor')
      ->setDescription('Monitor current App sync activity')
      ->setHelp("This command will output App Sync activity. [drudock sync:monitor]");
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);

    $io->section("SYNC ::: Monitor");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . 'logs -f unison  2>&1';
      $application->runcommand($command, $io);
    }
  }
}