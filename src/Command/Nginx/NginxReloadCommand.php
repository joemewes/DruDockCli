<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\NginxReloadCommand.
 */

namespace Docker\Drupal\Command\Nginx;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class NginxReloadCommand
 * @package Docker\Drupal\Command\Nginx
 */
class NginxReloadCommand extends Command {

  protected function configure() {
    $this
      ->setName('nginx:reload')
      ->setDescription('Reload nginx activity')
      ->setHelp("This command will reload NGINX config.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);

    $io->section("Nginx ::: reload");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . 'exec -T nginx nginx -s reload 2>&1';
      $application->runcommand($command, $io);
    }
  }
}
