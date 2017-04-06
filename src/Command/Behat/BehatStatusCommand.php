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
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class BehatStatusCommand
 * @package Docker\Drupal\Command
 */
class BehatStatusCommand extends Command {
  protected function configure() {
    $this
      ->setName('behat:status')
      ->setDescription('Runs example command against running APP and current config')
      ->setHelp("Currently hardcoded options [behat:status]");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $container_application = new ApplicationContainerExtension();

    $io = new DockerDrupalStyle($input, $output);

    $config = $application->getAppConfig($io);

    if ($config) {
      $type = $config['apptype'];
    }

    $cmd = '--config /root/behat/behat.yml --suite global_features --profile local --tags about';
    $io->section("BEHAT ::: Example :: " . $cmd);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $application->getComposePath($appname, $io) . 'exec behat behat ' . $cmd;
      $application->runcommand($command, $io);
    }

  }
}