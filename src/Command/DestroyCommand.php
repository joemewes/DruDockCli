<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Docker\Drupal\Style\DockerDrupalStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class DestroyCommand extends Command {

  protected function configure() {
    $this
      ->setName('build:destroy')
      ->setAliases(['destroy'])
      ->setDescription('Disable and delete APP and containers')
      ->setHelp("This command will completely remove all containers and volumes for the current APP via the docker-compose.yml file.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $application = $this->getApplication();

    $io = new DockerDrupalStyle($input, $output);
    $io->section("REMOVING APP");

    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(
      'Are you sure you want to delete this app? [y/n] : ',
      FALSE,
      '/^(y)/i'
    );

    if (!$helper->ask($input, $output, $question)) {
      return;
    }

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $appreqs = $config['reqs'];
    }

    if (isset($appreqs) && ($appreqs == 'Basic' || $appreqs == 'Full')) {
      if ($application->checkForAppContainers($appname, $io)) {
          $command = $application->getComposePath($appname, $io) . ' down -v 2>&1';
          $application->runcommand($command, $io);
      }
    }

    if(isset($appreqs) && $appreqs == 'Prod') {
      if ($application->checkForAppContainers($appname, $io)) {
        $command = $application->getComposePath($appname, $io) . ' down -v 2>&1';
        $application->runcommand($command, $io);
      }

      $command = $application->getDataComposePath($appname, $io) . ' down -v 2>&1';
      $application->runcommand($command, $io);

      $command = $application->getProxyComposePath($appname, $io) . ' down -v 2>&1';
      $application->runcommand($command, $io);

    }

  }

}
