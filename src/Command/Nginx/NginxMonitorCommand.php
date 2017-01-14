<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Nginx;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class NginxMonitorCommand
 * @package Docker\Drupal\Command\Nginx
 */
class NginxMonitorCommand extends Command {

  protected function configure() {
    $this
      ->setName('nginx:log')
      ->setDescription('Monitor nginx activity')
      ->setHelp("This command will output NGINX activity.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    $io->section("Nginx ::: Monitor");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($application->checkForAppContainers($appname, $io)) {
      $command = $application->getComposePath($appname, $io) . 'exec -T nginx tail -f /var/log/nginx/app-error.log 2>&1';
      $application->runcommand($command, $io);
    }
  }

}
