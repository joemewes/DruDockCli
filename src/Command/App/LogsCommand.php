<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\StatusCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class LogsCommand
 * @package Docker\Drupal\Command
 */
class LogsCommand extends Command {
  protected function configure() {
    $this
      ->setName('app:logs')
      ->setAliases(['al'])
      ->setDescription('Get logs of all containers')
      ->setHelp("This command will output a logs of all running app containers.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);
    $io->section("HEALTHCHECK");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }
    else {
      $appname = 'app';
    }

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $command = $container_application->getComposePath($system_appname, $io) . 'logs -f';
    $application->runcommand($command, $io);
  }
}