<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Docker\Drupal\Style\DockerDrupalStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class RestartCommand extends Command {

  protected function configure() {
    $this
      ->setName('docker:restart')
      ->setAliases(['restart'])
      ->setDescription('Restart current APP containers')
      ->setHelp("This command will restart all containers for the current APP via the docker-compose.yml file.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $container_application = new ApplicationContainerExtension();

    $io = new DockerDrupalStyle($input, $output);
    $io->section("RESTARTING CONTAINERS");


    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $application->getComposePath($appname, $io) . 'restart 2>&1';
      $application->runcommand($command, $io);
    }
  }
}