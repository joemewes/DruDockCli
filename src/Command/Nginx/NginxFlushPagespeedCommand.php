<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\NginxFlushPagespeedCommand.
 */

namespace Docker\Drupal\Command\Nginx;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class NginxFlushPagespeedCommand
 *
 * @package Docker\Drupal\Command\Nginx
 */
class NginxFlushPagespeedCommand extends Command {

  protected function configure() {
    $this
      ->setName('nginx:flush')
      ->setDescription('Flush nginx cache')
      ->setHelp("This command will flush NGINX pagespeed cache.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);

    $io->section("Nginx ::: flush");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . 'exec -T nginx bash -c "rm -rf /var/ngx_pagespeed_cache/*" 2>&1';
      $application->runcommand($command, $io);
    }
  }
}
