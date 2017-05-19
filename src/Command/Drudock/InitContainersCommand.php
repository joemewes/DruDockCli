<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drudock;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class InitContainersCommand extends Command {

  protected function configure() {
    $this
      ->setName('drudock:init:containers')
      ->setAliases(['init:ct'])
      ->setDescription('Create APP containers')
      ->setHelp("This command will create app containers from https://hub.docker.com for the current APP via the docker-compose.yml file.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);
    $io->section("UPDATING CONTAINERS");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $io->warning("Container for this app already exist.  Try `drudock up:ct`");
      return;
    }

    $command = $container_application->getComposePath($appname, $io) . ' pull 2>&1';
    $application->runcommand($command, $io);

    $command = $container_application->getComposePath($appname, $io) . ' up -d --force-recreate 2>&1';
    $application->runcommand($command, $io);
  }
}
