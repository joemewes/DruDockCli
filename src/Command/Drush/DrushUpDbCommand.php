<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DockerDrupalStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class DrushUpDbCommand extends Command {

  protected function configure() {
    $this
      ->setName('drush:updb')
      ->setDescription('Run Drush updb')
      ->setHelp("This command will run all pending database updates for the current Drupal app.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $container_application = new ApplicationContainerExtension();

    $io = new DockerDrupalStyle($input, $output);
    $io->section("PHP ::: drush updb -y");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $application->getComposePath($appname, $io) . ' exec -T php drush updb -y';
      $application->runcommand($command, $io);
    }
  }

}
