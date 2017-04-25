<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Docker\Drupal\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DruDockStyle;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Zippy\Zippy;
use GuzzleHttp\Client;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Docker\Drupal\Extension\ApplicationContainerExtension;


/**
 * Define constants
 */

// Config constants.
const QUESTION = 'question';
const DATE_FORMAT = 'Y-m-d--H-i-s';

/**
 * Class InitCommand
 *
 * @package Docker\Drupal\ContainerAwareCommand
 */
class InitCommand extends ContainerAwareCommand {

  protected function configure() {
    $this
      ->setName('env:init')
      ->setDescription('Fetch and build DruDock containers')
      ->setHelp('This command will fetch the specified DruDock config, download and build all necessary images.  NB: The first time you run this command it will need to download 4GB+ images from DockerHUB so make take some time.  Subsequent runs will be much quicker.')
      ->addArgument('appname', InputArgument::OPTIONAL, 'Specify NAME of application to build [app-dd-mm-YYYY]')
      ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]')
      ->addOption('dist', 'r', InputOption::VALUE_OPTIONAL, 'Specify app requirements [Development,Production,Feature]')
      ->addOption('src', 'g', InputOption::VALUE_OPTIONAL, 'Specify app src [New, Git]')
      ->addOption('git', 'gs', InputOption::VALUE_OPTIONAL, 'Git repository URL')
      ->addOption('apphost', 'p', InputOption::VALUE_OPTIONAL, 'Specify preferred host path [drudock.dev]')
      ->addOption('services', 's', InputOption::VALUE_OPTIONAL, 'Select app services [UNISON, PHP, NGINX, MYSQL, SOLR, REDIS, MAILCATCHER]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $config_application = new ApplicationConfigExtension();

    $io = new DruDockStyle($input, $output);
    $fs = new Filesystem();
    $client = new Client();
    $zippy = Zippy::load();

    // Check if this folder is has APP config.
    if (file_exists('.config.yml')) {
      $io->error('You\'re currently in an APP directory');
      return;
    }

    // Setup app config.
    $appname = $config_application->getSetAppname($io, $input, $output, $this);
    $src = $config_application->getSetSource($io, $input, $output, $this);
    if ($src) {
      $gitrepo = $config_application->getSetSCMSource($io, $input, $output, $src, $this);
    }
    $dist = $config_application->getSetDistribution($io, $input, $output, $this);
    $type = $config_application->getSetType($io, $input, $output, $this);
    $apphost = $config_application->getSetHost($io, $input, $output, $this);
    $service_types = $config_application->getSetServices($io, $input, $output, $this);

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

    $config_application->writeDockerComposeConfig($io, $config);

    $yaml = Yaml::dump($config);
    file_put_contents($system_appname . '/.config.yml', $yaml);

    if ($application->getOs() == 'Darwin' && isset($appname)) {

      $message = "If prompted, please type admin password to add app localhost details \n and COPY ifconfig alias.plist to /Library/LaunchDaemons/";
      $io->info(' ');
      $io->info($message);
      $io->info(' ');
      $config_application->setHostConfig($fs, $client, $zippy, 'drudock.dev', $io, $appname);
    }

    $message = 'Fetching DruDock v' . $application->getVersion();
    $io->info(' ');
    $io->note($message);

    $this->getDruDock($application, $io, $system_appname);

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
   */
  private function getDruDock($application, $io, $appname) {

    $message = 'Download and configure DruDock.... This may take a few minutes....';
    $io->note($message);

    $dockerlogs = 'docker-compose -f ' . $appname . '/docker_' . $appname . '/docker-compose.yml logs -f';
    $application->runcommand($dockerlogs, $io, TRUE);

    $dockercmd = 'docker-compose -f ' . $appname . '/docker_' . $appname . '/docker-compose.yml pull';
    $application->runcommand($dockercmd, $io, TRUE);

  }
}
