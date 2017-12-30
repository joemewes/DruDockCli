<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class DemoCommand
 *
 * @package Docker\Drupal\Command
 */
class DrushClearCacheCommand extends Command {

  protected function configure() {
    $this
      ->setName('drush:cc')
      ->setAliases('dcc')
      ->setDescription('Run drush cache clear ')
      ->setHelp("This command will clear Drupal APP caches.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);

    if (!$config = $application->getAppConfig($io)) {
      $io->error('No config found. You\'re not currently in an Drupal APP directory');
      return;
    }
    else {
      $appname = $config['appname'];
    }

    switch ($config['apptype']) {
      case 'D8':
        $cmd = 'cr all';
        break;
      case 'D7':
        $cmd = 'cc all';
        break;
      default:
        $io->error('You\'re not currently in an Drupal APP directory');
        return;
    }

    $io->section('PHP ::: drush ' . $cmd);

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . ' exec -T php drush ' . $cmd;
      $application->runcommand($command, $io);
    }
  }

}
