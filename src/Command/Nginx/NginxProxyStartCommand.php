<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\NginxProxyStartCommand.
 */

namespace Docker\Drupal\Command\Nginx;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Yaml\Yaml;


/**
 * Class NginxProxyStartCommand
 *
 * @package Docker\Drupal\Command\Nginx
 */
class NginxProxyStartCommand extends Command {

  const APPNAME = 'appname';

  const DIST = 'dist';

  const HOST = 'host';

  protected function configure() {
    $this
      ->setName('nginx:proxy:start')
      ->setDescription('Start nginx proxy')
      ->setHelp("Launch a globally useful DruDock nginx-proxy for all running apps.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);

    $io->section("Proxy ::: Start");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config[self::APPNAME];
      $apptype = $config[self::DIST];
    }

    $container_application->createProxyNetwork($io);

    if (isset($appname) && $container_application->checkForAppContainers($appname, $io)) {

      // Check if depends health checks are required.
      if (isset($apptype) && $apptype !== 'Development') {
        $io->warning("Nginx proxy is for local development purposes and should be used with Development apps.");
      }

      $command = 'docker ps -a -f name=drudock-proxy | grep drudock-proxy';
      if (shell_exec($command)) {
        $io->info("Running proxy container.");
        $command = 'docker start drudock-proxy';
        $application->runcommand($command, $io);
      }
      else {
        $io->info("Creating proxy container.");
        $command = 'docker run -d -p 80:80 -v /var/run/docker.sock:/tmp/docker.sock:ro --name drudock-proxy --net drudock-frontend 4alldigital/drudock-nginx-proxy';
        $application->runcommand($command, $io);
      }

      $system_appname = strtolower(str_replace(' ', '', $config[self::APPNAME]));
      $base_yaml = file_get_contents('./docker_' . $system_appname . '/docker-compose.yml');
      $base_compose = Yaml::parse($base_yaml);

      if (!in_array('proxy', $base_compose['services']['nginx']["networks"])) {
        $base_compose['services']['nginx']["networks"][] = 'proxy';
        $base_compose['services']['nginx']['environment']['VIRTUAL_HOST'] = $config[self::HOST];
        $base_compose['services']['nginx']['environment']['VIRTUAL_NETWORK'] = 'nginx-proxy';
      }

      $base_compose['networks']['proxy']['external']['name'] = 'drudock-frontend';

      $app_yaml = Yaml::dump($base_compose, 8, 2);
      $application->renderFile('./docker_' . $system_appname . '/docker-compose.yml', $app_yaml);
      $command = $container_application->getComposePath($appname, $io) . 'up -d';
      $application->runcommand($command, $io);
    }
  }
}
