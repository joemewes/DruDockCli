<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class StopCommand extends Command {

  protected function configure() {
    $this
      ->setName('docker:stop')
      ->setAliases(['stop'])
      ->setDescription('Stop current APP containers')
      ->setHelp("Example : [dockerdrupal stop]");;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    $io->section("APP ::: Stopping " . $appname . " containers");

    if ($application->checkForAppContainers($appname, $io)) {
      $command = $application->getComposePath($appname, $io) . ' stop 2>&1';
      $application->runcommand($command, $io);
    }
  }
}