<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drudock;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Class DemoCommand
 *
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

    $application = new Application();
    $cta = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);
    $io->section("REMOVING APP");

    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(
      'Are you sure you want to delete this app? [y/n] : ',
      TRUE,
      '/^(y)/i'
    );

    if (!$helper->ask($input, $output, $question)) {
      return;
    }

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $dist = $config['dist'];
    }

    if (isset($dist) && ($dist == 'Development')) {
      if ($cta->checkForAppContainers($appname, $io)) {
        $command = $cta->getComposePath($appname, $io) . ' down -v 2>&1';
        $application->runcommand($command, $io);
      }
    }

    if (isset($dist) && ($dist == 'Feature')) {
      if ($cta->checkForAppContainers($appname, $io)) {
        $command = $cta->getComposePath($appname, $io) . ' down -v 2>&1';
        $application->runcommand($command, $io);
      }
    }

    if (isset($dist) && $dist == 'Production') {
      if ($cta->checkForAppContainers($appname, $io)) {
        $command = $cta->getComposePath($appname, $io) . ' down -v 2>&1';
        $application->runcommand($command, $io);
      }

      $command = $cta->getDataComposePath($appname, $io) . ' down -v 2>&1';
      $application->runcommand($command, $io);

      $command = $application->getProxyComposePath($appname, $io) . ' down -v 2>&1';
      $application->runcommand($command, $io);
    }

    if (isset($dist) && $dist == 'Staging') {
      if ($cta->checkForAppContainers($appname, $io)) {
        $command = $cta->getComposePath($appname, $io) . ' down -v 2>&1';
        $application->runcommand($command, $io);
      }

      $command = $cta->getDataComposePath($appname, $io) . ' down -v 2>&1';
      $application->runcommand($command, $io);
    }

  }

}
