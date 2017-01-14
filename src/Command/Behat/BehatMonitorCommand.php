<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Behat;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class BehatMonitorCommand
 * @package Docker\Drupal\Command
 */
class BehatMonitorCommand extends Command {

  protected function configure() {
    $this
      ->setName('behat:monitor')
      ->setDescription('Launch behat VNC viewer')
      ->setHelp("DD used Selenium:debug containers and this will allow watching of automated tests via OS default VNC viewer.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();

    $io = new DockerDrupalStyle($input, $output);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($application->checkForAppContainers($appname, $io)) {

      $io->section('EXEC behat ' . $cmd);

      $command = 'open vnc://:secret@localhost:$(docker inspect --format \'{{ (index (index .NetworkSettings.Ports "5900/tcp") 0).HostPort }}\' $(docker ps --format {{.Names}} | grep firefox))';
      $application->runcommand($command, $io);
    }
  }

}
