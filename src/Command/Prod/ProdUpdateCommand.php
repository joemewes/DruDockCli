<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Prod;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class ProdUpdateCommand
 * @package Docker\Drupal\Command\redis
 */
class ProdUpdateCommand extends Command {
  protected function configure() {
    $this
      ->setName('prod:update')
      ->setDescription('Rebuild app and deploy latest code into app containers')
      ->setHelp("Deploy host /app code into new/latest build [drudock prod:update]");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);

    $io->section("PROD ::: Update");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $appdist = $config['dist'];
    }

    if (isset($appdist) && !$appdist == 'Prod') {
      $io->warning("This is not a production app.");
      return;
    }

    if (isset($appdist) && $appdist == 'Prod') {

      if ($container_application->checkForAppContainers($appname, $io)) {

        $date = date('Y-m-d--H-i-s');
        $system_appname = strtolower(str_replace(' ', '', $appname));
        $projectname = $system_appname . '--' . $date;

        // RUN APP BUILD.
        $command = 'docker-compose -f ./docker_' . $system_appname . '/docker-compose.yml --project-name=' . $projectname . ' build --no-cache';
        $application->runcommand($command, $io);

        // RUN APP.
        $command = 'docker-compose -f ./docker_' . $system_appname . '/docker-compose.yml --project-name=' . $projectname . ' up -d app';
        $application->runcommand($command, $io);

        // START PROJECT.
        $command = 'docker-compose -f ./docker_' . $system_appname . '/docker-compose.yml --project-name=' . $projectname . ' up -d';
        $application->runcommand($command, $io);

        $previous_build = end($config['builds']);
        $previous_build_projectname = $system_appname . '--' . $previous_build;
        // STOP PREVIOUS BUILD.
        $command = 'docker-compose -f ./docker_' . $system_appname . '/docker-compose.yml --project-name=' . $previous_build_projectname . ' down -v';
        $application->runcommand($command, $io);

        $config['builds'][] = $date;
        $application->setAppConfig($config, $io);

      }
    }
  }
}
