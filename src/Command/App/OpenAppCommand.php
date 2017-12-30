<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class OpenAppCommand
 *
 * @package Docker\Drupal\Command
 */
class OpenAppCommand extends Command {

  protected $app;

  protected $cfa;

  protected $cta;

  protected function configure() {
    $this
      ->setName('app:open')
      ->setAliases(['open'])
      ->setDescription('Open APP in default browser.')
      ->setHelp("Example : [drudock open]");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $hosts = explode(' ', $config['host']);
      $host = $hosts[0];
    }
    else {
      $appname = 'app';
      $host = 'drudock.localhost';
    }

    $io->section("APP ::: Opening " . $appname);

    if ($container_application->checkForAppContainers($appname, $io)) {
      $this->cfa = new ApplicationConfigExtension();
      $system_appname = strtolower(str_replace(' ', '', $appname));
      $nginx_port = $this->cfa->containerPort($system_appname,'nginx', '80');
      exec('python -mwebbrowser http://' . $host . ':' . $nginx_port);
    }
  }
}
