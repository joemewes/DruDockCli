<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Nginx;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DockerDrupalStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Alchemy\Zippy\Zippy;
use GuzzleHttp\Client;

/**
 * Class NginxSetHostCommand
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
    $application = $this->getApplication();
    $container_application = new ApplicationContainerExtension();
    $fs = new Filesystem();
    $client = new Client();
    $zippy = Zippy::load();

    $io = new DockerDrupalStyle($input, $output);
    $io->section("Nginx ::: add host");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
      $apphost = $config['host'];
    }

    if (!isset($apphost)) {
      $apphost = 'docker.dev';
    }

    $currenthost = $apphost;
    $helper = $this->getHelper('question');
    $question = new Question('Please enter new hostname : [' . $currenthost . '] ', $currenthost);
    $newhost = $helper->ask($input, $output, $question);

    if ($application->getOs() == 'Darwin') {
      $application->addHostConfig($fs, $client, $zippy, $newhost, $io, TRUE);
    }

    if (file_exists('.config.yml')) {
      $config = Yaml::parse(file_get_contents('.config.yml'));
      $config['host'] = $newhost;
      $yaml = Yaml::dump($config);
      file_put_contents('.config.yml', $yaml);
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

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $application->getComposePath($appname, $io) . 'exec -T nginx nginx -s reload 2>&1';
      $application->runcommand($command, $io);
    }
  }
}
