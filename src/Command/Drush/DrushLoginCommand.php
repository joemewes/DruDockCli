<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class DrushLoginCommand
 *
 * @package Docker\Drupal\Command
 */
class DrushLoginCommand extends Command {

  protected function configure() {
    $this
      ->setName('drush:uli')
      ->setAliases(['duli'])
      ->setDescription('Run Drush ULI')
      ->setHelp("This command will output a login URL.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);
    $io->section('PHP ::: drush uli');

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $hosts = explode(' ', $config['host']);
      $host = $hosts[0];
    } else {
      $appname = 'app';
      $host = 'drudock.localhost';
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . ' exec -T php drush -l ' . $host .  ' uli';
      $uli_path = exec($command);
      exec('python -mwebbrowser ' . $uli_path);
    }

  }

}
