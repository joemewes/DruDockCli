<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Docker\Drupal\Style\DockerDrupalStyle;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class DemoCommand
 *
 * @package Docker\Drupal\Command
 */
class StartCommand extends Command {

  protected function configure() {
    $this
      ->setName('docker:start')
      ->setAliases(['start'])
      ->setDescription('Start current APP containers')
      ->setHelp("Example : [dockerdrupal start]");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $appreqs = $config['reqs'];
    }

    $io->section("APP ::: Starting " . $appname . " containers");

    if (exec("docker ps | grep docker | wc -l") > 0) {

      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion('You have other containers running. Would you like to stop them? ', FALSE);

      if ($helper->ask($input, $output, $question)) {
        $io->info(' ');
        $command = "docker stop $(docker ps -q)";
        $application->runcommand($command, $io);
      }

      if ($application->checkForAppContainers($appname, $io)) {
        $command = $application->getComposePath($appname, $io) . ' start 2>&1';
        $application->runcommand($command, $io);
      }

    }
    else {
      if ($application->checkForAppContainers($appname, $io)) {
        $command = $application->getComposePath($appname, $io) . ' start 2>&1';
        $application->runcommand($command, $io);
      }
    }

    if ($application->checkForAppContainers($appname, $io)) {
      $system_appname = strtolower(str_replace(' ', '', $appname));
      $fs = new Filesystem();
      // If Prod app start networks.
      if ($fs->exists('./docker_' . $system_appname . '/docker-compose-nginx-proxy.yml')) {
        $command = 'docker-compose -f ./docker_' . $system_appname . '/docker-compose-nginx-proxy.yml start 2>&1';
        $application->runcommand($command, $io);
      }
      if ($fs->exists('./docker_' . $system_appname . '/docker-compose-data.yml')) {
        $command = 'docker-compose -f ./docker_' . $system_appname . '/docker-compose-data.yml start 2>&1';
        $application->runcommand($command, $io);
      }
    }
  }
}