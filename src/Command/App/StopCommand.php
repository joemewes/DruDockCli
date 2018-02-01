<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\StopCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class StopCommand
 *
 * @package Docker\Drupal\Command
 */
class StopCommand extends Command {

  protected function configure() {
    $this
      ->setName('app:stop')
      ->setAliases(['stop'])
      ->setDescription('Stop current APP containers')
      ->setHelp("Example : [drudock stop]");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }
    else {
      $appname = 'app';
    }

    $io->section("APP ::: Stopping " . $appname . " containers");

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . ' stop 2>&1';
      $application->runcommand($command, $io);
    }
  }
}
