<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use const Docker\Drupal\Extension\PRODUCTION;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DruDockStyle;
use Symfony\Component\Yaml\Yaml;
use Docker\Drupal\Extension\ApplicationConfigExtension;

/**
 * Define constants
 */

// Config constants.
const QUESTION = 'question';
const DATE_FORMAT = 'Y-m-d--H-i-s';
const DC = 'docker-compose -f ';
const DOCKER = '/docker_';

/**
 * Class InitCommand
 *
 * @package Docker\Drupal\ContainerAwareCommand
 */
class InitCommand extends ContainerAwareCommand {

  protected $app;

  protected $cfa;

  protected $cta;

  protected function configure() {
    $this
      ->setName('app:init')
      ->setAliases(['ai'])
      ->setDescription('Fetch and build DruDock containers')
      ->setHelp('This command will fetch the specified DruDock config, download and build all necessary images.  NB: The first time you run this command it will need to download 4GB+ images from DockerHUB so make take some time.  Subsequent runs will be much quicker.')
      ->addOption('appname', 'a', InputOption::VALUE_OPTIONAL, 'Specify NAME of application to build [app-dd-mm-YYYY]')
      ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]')
      ->addOption('dist', 'r', InputOption::VALUE_OPTIONAL, 'Specify app requirements [Development,Feature]')
      ->addOption('src', 'g', InputOption::VALUE_OPTIONAL, 'Specify app src [New, Git]')
      ->addOption('git', 'gs', InputOption::VALUE_OPTIONAL, 'Git repository URL')
      ->addOption('apphost', 'p', InputOption::VALUE_OPTIONAL, 'Specify preferred host path [drudock.localhost]')
      ->addOption('services', 's', InputOption::VALUE_OPTIONAL, 'Select app services [PHP, NGINX, MYSQL, SOLR, REDIS, MAILHOG]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $this->cfa = new ApplicationConfigExtension();

    $io = new DruDockStyle($input, $output);
    $fs = new Filesystem();

    // Check if this folder is has APP config.
    if (file_exists('.config.yml')) {
      $io->error('You\'re currently in an APP directory');
      return;
    }

    // Setup app config.
    $appname = $this->cfa->getSetAppname($io, $input, $output, $this);
    $src = $this->cfa->getSetSource($io, $input, $output, $this);
    if ($src) {
      $gitrepo = $this->cfa->getSetSCMSource($io, $input, $output, $src, $this);
    }
    $dist = $this->cfa->getSetDistribution($io, $input, $output, $this);
    $type = $this->cfa->getSetType($io, $input, $output, $this);
    $apphost = $this->cfa->getSetHost($io, $input, $output, $this);
    $service_types = $this->cfa->getSetServices($io, $input, $output, $this);

    // Setup app initial folder structure.
    $system_appname = strtolower(str_replace(' ', '', $appname));
    if (!$fs->exists($system_appname)) {
      $fs->mkdir($system_appname, 0755);
    }
    else {
      $io->error('This app already exists');
      return;
    }

    // SETUP APP CONFIG FILE.
    $config = [
      'appname' => $appname,
      'apptype' => $type,
      'host' => $apphost,
      'dist' => $dist,
      'src' => $src,
      'services' => $service_types,
      'repo' => isset($gitrepo) ? $gitrepo : '',
      'created' => $date = date(DATE_FORMAT),
      'builds' => [
        $date = date(DATE_FORMAT),
      ],
      'drudock' => [
        'version' => $application->getVersion(),
        'date' => $date,
      ],
    ];

    // Add Mailhog to local development setup.
    if(in_array('PHP', $config['services']) && $config['dist'] === 'Development'){
      $config['services'][] = 'MAILHOG';
    }

    $this->cfa->writeDockerComposeConfig($io, $config);

    $yaml = Yaml::dump($config);
    file_put_contents($system_appname . '/.config.yml', $yaml);

    if ($application->getOs() == 'Darwin' && isset($appname) && !file_exists('/Library/LaunchDaemons/com.4alldigital.drudock.plist')) {
      $message = "If prompted, please type admin password to add app localhost details \n and COPY ifconfig alias.plist to /Library/LaunchDaemons/";
      $io->info(' ');
      $io->info($message);
      $io->info(' ');
      $this->cfa->setHostConfig('drudock.localhost', $io, $system_appname);
    }

    $message = 'Fetching DruDock v' . $application->getVersion();
    $io->info(' ');
    $io->note($message);

    $this->getDruDock($application, $io, $system_appname, $config);

    $io->info(' ');
    $io->section("DruDock ::: Ready");
    $info = 'Go to app directory [cd ' . $system_appname . '] and run [drudock build:init]';
    $io->info($info);
    $io->info(' ');
  }

  /**
   * @param $application
   * @param $io
   * @param $appname
   * @param $config
   */
  private function getDruDock($application, $io, $appname, $config) {

    $message = 'Download and configure DruDock.... This may take a few minutes....';
    $io->note($message);

    $dockerlogs = DC . $appname . DOCKER . $appname . '/docker-compose.yml logs -f';
    $application->runcommand($dockerlogs, $io, TRUE);

    $dockercmd = DC . $appname . DOCKER . $appname . '/docker-compose.yml pull';
    $application->runcommand($dockercmd, $io, TRUE);

    if ($config['dist'] === PRODUCTION) {
      $dockercmd = DC . $appname . DOCKER . $appname . '/docker-compose-data.yml pull';
      $application->runcommand($dockercmd, $io, TRUE);

      $dockercmd = DC . $appname . DOCKER . $appname . '/docker-compose-nginx-proxy.yml pull';
      $application->runcommand($dockercmd, $io, TRUE);
    }
  }
}
