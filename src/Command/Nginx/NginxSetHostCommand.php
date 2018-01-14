<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Nginx;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Alchemy\Zippy\Zippy;
use GuzzleHttp\Client;

/**
 * Class NginxSetHostCommand
 *
 * @package Docker\Drupal\Command\Nginx
 */
class NginxSetHostCommand extends Command {

  protected function configure() {
    $this
      ->setName('nginx:sethost')
      ->setDescription('Add nginx host to DD and host OS')
      ->setHelp("This command will add a host server_name & reload NGINX config.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $cta = new ApplicationContainerExtension();
    $cfa = new ApplicationConfigExtension();

    $io = new DruDockStyle($input, $output);
    $io->section("Nginx ::: add host");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $apphost = $config['host'];
    }
    $system_appname = isset($appname) ? strtolower(str_replace(' ', '', $appname)) : 'app';

    if (!isset($apphost)) {
      $apphost = 'drudock.localhost';
    }

    $currenthost = $apphost;
    $helper = $this->getHelper('question');
    $question = new Question('Please enter new hostname : [' . $currenthost . '] ', $currenthost);
    $newhost = $helper->ask($input, $output, $question);

    if ($application->getOs() == 'Darwin') {
      $cfa->setHostConfig($newhost, $io, $system_appname);
    }

    if (file_exists('.config.yml')) {
      // Update app config.yaml.
      $config = Yaml::parse(file_get_contents('.config.yml'));
      $config['host'] = $newhost;
      $yaml = Yaml::dump($config);
      file_put_contents('.config.yml', $yaml);
      // Update docker-compose.yaml file.
      $base_yaml = file_get_contents('./docker_' . $system_appname . '/docker-compose.yml');
      $base_compose = Yaml::parse($base_yaml);
      $base_compose['services']['nginx']['environment']['VIRTUAL_HOST'] = $config['host'];
      $app_yaml = Yaml::dump($base_compose, 8, 2);
      $application->renderFile('./docker_' . $system_appname . '/docker-compose.yml', $app_yaml);
    }
    else {
      $io->error('You\'re not currently in an APP directory. APP .config.yml not found.');
      exit;
    }

    if (file_exists('./app/www/sites/default/drushrc.php')) {
      $drushrc = file_get_contents('./app/www/sites/default/drushrc.php');
      $newhosts = explode(' ', $newhost);
      $newhost = $newhosts[0];
      $currenthosts = explode(' ', $currenthost);
      $host = $currenthosts[0];
      $drushrc = str_replace($host, $newhost, $drushrc);
      file_put_contents('./app/www/sites/default/drushrc.php', $drushrc);
    }

    $application->setNginxHost($io);

    if ($cta->checkForAppContainers($appname, $io)) {
      $command = $cta->getComposePath($appname, $io) . 'exec -T nginx nginx -s reload 2>&1';
      $application->runcommand($command, $io);
    }
  }
}
