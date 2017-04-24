<?php

/**
 * @file
 * Contains \Docker\Drupal\Extension\DemoCommand.
 */

namespace Docker\Drupal\Extension;

use Docker\Drupal\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DruDockStyle;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Zippy\Zippy;
use GuzzleHttp\Client;

// Config constants.
const DEV_MYSQL_PASS = 'DEVPASSWORD';
const LOCALHOST = '127.0.0.1';
const QUESTION = 'question';
const DISALLOWED_MSG = ' not allowed.';
const APPNAME = 'appname';
const SERVICES = 'services';
const VOLUMES = 'volumes';
const NETWORKS = 'networks';
const DATE_FORMAT = 'Y-m-d--H-i-s';


/**
 * Class ApplicationConfigExtension
 *
 * @package Docker\Drupal\Extension
 */
class ApplicationConfigExtension extends Application {

  /**
   * GET AND SET APPNAME.
   *
   * @param $io
   * @param $input
   * @param $output
   * @param $cmd
   *
   * @return mixed
   */
  function getSetAppname($io, $input, $output, $cmd) {
    $appname = $input->getArgument(APPNAME);
    $date = date(DATE_FORMAT);
    if (!isset($appname)) {
      $io->title("SET APP NAME");
      $helper = $cmd->getHelper(QUESTION);
      $question = new Question('Enter App name [drudock_app_' . $date . '] : ', 'my-app-' . $date);
      $appname = $helper->ask($input, $output, $question);
    }
    return $appname;
  }

  /**
   * GET AND SET APP SOURCE.
   *
   * @param $io
   * @param $input
   * @param $output
   * @param $cmd
   *
   * @return mixed
   */
  function getSetSource($io, $input, $output, $cmd) {
    $src = $input->getOption('src');
    $available_src = ['New', 'Git'];

    if ($src && !in_array($src, $available_src)) {
      $io->warning('APP SRC : ' . $src . DISALLOWED_MSG);
      $src = NULL;
    }

    if (!isset($src)) {
      $io->info(' ');
      $io->title("SET APP SOURCE");
      $helper = $cmd->getHelper(QUESTION);
      $question = new ChoiceQuestion(
        'Is this app a new build or loaded from a remote GIT repository [New, Git] : ',
        $available_src,
        'New'
      );
      $src = $helper->ask($input, $output, $question);
    }
    return $src;
  }

  /**
   * GET AND SET APP SCM SOURCE.
   *
   * @param $io
   * @param $input
   * @param $output
   * @param $src
   * @param $cmd
   *
   * @return string
   */
  function getSetSCMSource($io, $input, $output, $src, $cmd) {
    $gitrepo = $input->getOption('git');
    if ($src == 'New') {
      $gitrepo = '';
    }
    if (!isset($gitrepo)) {
      $io->title("SET APP GIT URL");
      $helper = $cmd->getHelper(QUESTION);
      $question = new Question('Enter remote GIT url [https://github.com/<me>/<myapp>.git] : ');
      $gitrepo = $helper->ask($input, $output, $question);
    }
    return $gitrepo;
  }

  /**
   * GET AND SET APP REQUIREMENTS.
   *
   * @param $io
   * @param $input
   * @param $output
   * @param $cmd
   *
   * @return null
   */
  function getSetDistribution($io, $input, $output, $cmd) {
    $dist = $input->getOption('dist');
    $available_dist = ['Development', 'Production', 'Feature'];

    if ($dist && !in_array($dist, $available_dist)) {
      $io->warning('DIST : ' . $dist . DISALLOWED_MSG);
      $dist = NULL;
    }

    if (!$dist) {
      $io->info(' ');
      $io->title("SET APP DIST");
      $helper = $cmd->getHelper(QUESTION);
      $question = new ChoiceQuestion(
        'Select your APP distribution : ',
        $available_dist,
        'basic'
      );
      $dist = $helper->ask($input, $output, $question);
    }
    return $dist;
  }

  /**
   * GET AND SET APP TYPE.
   *
   * @param $io
   * @param $input
   * @param $output
   * @param $cmd
   *
   * @return null
   */
  function getSetType($io, $input, $output, $cmd) {
    $type = $input->getOption('type');
    $available_types = ['DEFAULT', 'D7', 'D8'];

    if ($type && !in_array($type, $available_types)) {
      $io->warning('TYPE : ' . $type . DISALLOWED_MSG);
      $type = NULL;
    }

    if (!$type) {
      $io->info(' ');
      $io->title("SET APP TYPE");
      $helper = $cmd->getHelper(QUESTION);
      $question = new ChoiceQuestion(
        'Select your APP type [0] : ',
        $available_types,
        '0'
      );
      $type = $helper->ask($input, $output, $question);
    }
    return $type;
  }

  /**
   * GET AND SET APP PREFERRED HOST.
   *
   * @param $io
   * @param $input
   * @param $output
   * @param $cmd
   *
   * @return mixed
   */
  function getSetHost($io, $input, $output, $cmd) {
    $apphost = $input->getOption('apphost');

    if (!$apphost) {
      $io->info(' ');
      $io->title("SET APP HOSTNAME");
      $helper = $cmd->getHelper(QUESTION);
      $question = new Question('Enter preferred app hostname [drudock.dev] : ', 'drudock.dev');
      $apphost = $helper->ask($input, $output, $question);
    }
    return $apphost;
  }

  /**
   * GET AND SET SERVICES.
   *
   * @param $io
   * @param $input
   * @param $output
   * @param $cmd
   *
   * @return null
   */
  function getSetServices($io, $input, $output, $cmd) {
    $service_types = $input->getOption(SERVICES);
    $available_services = [
      'UNISON',
      'PHP',
      'NGINX',
      'MYSQL',
      'SOLR',
      'REDIS',
      'MAILCATCHER',
    ];

    // Inline Services entry as comma separated string.
    if(is_string($service_types)){
      $service_types = explode(',',$service_types);
    }

    // Confirm valid service entry.
    if ($service_types && is_array($service_types)) {
      foreach ($service_types as $st) {
        if (!in_array($st, $available_services)) {
          $io->warning('SERVICES : ' . $service_types . DISALLOWED_MSG);
          $service_types = NULL;
        }
      }
    }

    // Get/Set Services manually.
    if (!$service_types) {
      $io->info(' ');
      $io->title("SET APP SERVICES");
      $helper = $cmd->getHelper(QUESTION);
      $question = new ChoiceQuestion(
        'Select your APP services [eg. 0,2,3,5]: ',
        $available_services,
        '0,1'
      );
      $question->setMultiselect(TRUE);
      $service_types = $helper->ask($input, $output, $question);
    }
    return $service_types;
  }

  /**
   * Verify mySQL container is ready.
   *
   * @param $io
   * @param $system_appname
   * @param $type
   */
  function verifyMySQL($io, $system_appname, $type) {
    // Check for running mySQL container before launching Drupal Installation.
    $io->text(' ');
    $io->warning('Waiting for mySQL service.');

    if ($type) {
      switch ($type) {
        case 'prod':
          $command = exec('docker port mysql 3306');
          $port = explode(':', $command);
          $db_port = $port[1];
          $db_name = 'prod';
          break;

        case 'stage':
          $command = "docker-compose -f docker_" . $system_appname . "/docker-compose-data.yml --project-name=" . $system_appname . "_data port db 3306";
          $port_info = exec($command);
          $port = explode(':', $port_info);
          $db_port = $port[1];
          $db_name = 'stage';
          break;

        default:
          $db_port = '3306';
          $db_name = 'dev_db';
      }
    }

    while (!@mysqli_connect(LOCALHOST, 'dev', DEV_MYSQL_PASS, $db_name, $db_port)) {
      $phases = ["|", "/", "-", "\\"];
      foreach ($phases AS $phase) {
        printf('%s%s', chr(8), $phase);
        usleep(100000); // Replace this with one iteration of doing stuff
      }
    }

    $io->text(' ');
    $io->success('mySQL CONNECTED');
  }

  /**
   * @param $fs
   * @param $client
   * @param $zippy
   * @param $newhost
   * @param $io
   * @param $appname
   */
  public function setHostConfig($fs, $client, $zippy, $newhost, $io, $appname) {
    // Add initial entry to hosts file.
    // OSX @TODO update as command for all systems and OS's.

    $ip = LOCALHOST;

    if ($config = $this->getAppConfig($io, $appname)) {
      $apphost = $config['host'];
      $appname = $config[APPNAME];
      $system_appname = strtolower(str_replace(' ', '', $appname));
    }
    else {
      $apphost = 'drudock.dev';
    }

    $hosts_file = '/etc/hosts';
    $app_host_config = "### " . $system_appname . "\n" . $ip . " " . $apphost . "\n###";
    $new_host_config = "### " . $system_appname . "\n" . $ip . " " . $newhost . "\n###";
    $hosts_file_contents = file_get_contents($hosts_file);

    if (!strpos($hosts_file_contents, $app_host_config)) {
      // Add new.
      $command = sprintf("echo '%s' | sudo tee -a %s >/dev/null", $new_host_config, $hosts_file);
      $this->runcommand($command, $io, TRUE);
    }
    else if($app_host_config !== $new_host_config) {
      // Replace existing.
      $hosts_file_contents = str_replace($app_host_config, $new_host_config, $hosts_file_contents);
      $command = 'echo "' . $hosts_file_contents . '" | sudo tee ' . $hosts_file;
      exec($command);
    }

    if (!file_exists('/Library/LaunchDaemons/com.4alldigital.drudock.plist')) {
      $this->tmpRemoteBundle('osx');
      $command = 'sudo cp -R /tmp/osx/com.4alldigital.drudock.plist /Library/LaunchDaemons/com.4alldigital.drudock.plist';
      $this->runcommand($command, $io, TRUE);
    }
  }

  function writeDockerComposeConfig($io, $config) {

    $system_appname = strtolower(str_replace(' ', '', $config[APPNAME]));
    $dist = $config['dist'];
    $dist_path = strtolower($dist);
    $services_compose_dest = $system_appname . '/docker_' . $system_appname . '/docker-compose.yml';


    if (!file_exists((__DIR__ . '/../../templates/base/docker-compose.yml'))) {
      $io->error('base template missing');
      exit;
    }
    else {
      $base_yaml = file_get_contents(__DIR__ . '/../../templates/base/docker-compose.yml');
    }

    switch ($dist) {
      case 'Development':
        if (isset($base_yaml)) {
          $base_compose = Yaml::parse($base_yaml);
        }
        $base_compose = $this->applyAppServices($io, $base_compose, $config, $dist_path);
        $app_yaml = Yaml::dump($base_compose, 8, 2);
        $this->renderFile($services_compose_dest, $app_yaml);

        break;
      case 'Production':
        echo 'production env';

        break;
      case 'Feature':
        echo 'feature branch';

        break;
      default:
        // Do nothing.
        break;
    }

    $this->getRemoteBundle($io, 'config_' . $dist_path, $system_appname . '/docker_' . $system_appname . '/config');
  }

  /**
   * Download remote bundle for temp usage.
   *
   * @param $file
   */
  public function tmpRemoteBundle($file) {
    $fs = new Filesystem();
    $client = new Client();
    $zippy = Zippy::load();

    $remote_file_path = $this::CDN . '/' . $file . '.tar.gz';
    $destination = '/tmp/' . $file . '.tar.gz';
    $client->get($remote_file_path, ['save_to' => $destination]);
    $archive = $zippy->open($destination);
    $archive->extract('/tmp/');
    $fs->remove($destination);
  }

  /**
   * Download remote bundle.
   *
   * @param $io
   * @param $file
   * @param $folder_name
   */
  public function getRemoteBundle($io, $file, $folder_name) {

    $fs = new Filesystem();
    $client = new Client();
    $zippy = Zippy::load();

    $remote_file_path = $this::CDN . '/' . $file . '.tar.gz';
    $destination = sys_get_temp_dir() . '/' . $file . '.tar.gz';
    $client->get($remote_file_path, ['save_to' => $destination]);
    $archive = $zippy->open($destination);
    $archive->extract('./');
    try {
      $fs->mirror($file, $folder_name);
      $fs->remove($file);
    } catch (IOExceptionInterface $e) {
      $io->warning('Error renaming folder');
    }
  }

  public function applyAppServices($io, $base_compose, $config, $dist_path) {

    if(is_string($config[SERVICES])){
      $services = explode(',',$config[SERVICES]);
    }elseif(is_array($config[SERVICES])){
      $services = $config[SERVICES];
    }else{
      $io->error('Invalid services options.');
      exit;
    }

    foreach ($services as $service) {
      $service_name = strtolower($service);
      $service_yaml = file_get_contents(__DIR__ . '/../../templates/' . $dist_path . '/services/' . $service_name . '.yml');
      $service_compose = Yaml::parse($service_yaml);
      $base_compose[SERVICES][$service_name] = $service_compose;

      if ($service === 'UNISON') {
        $vol = file_get_contents(__DIR__ . '/../../templates/' . $dist_path . '/' . VOLUMES . '/app-sync.yml');
        $vol_compose = Yaml::parse($vol);
        $base_compose[VOLUMES]['app-sync'] = $vol_compose;
      }
      if ($service === 'MYSQL') {
        $vol = file_get_contents(__DIR__ . '/../../templates/' . $dist_path . '/'. VOLUMES . '/mysql-data.yml');
        $vol_compose = Yaml::parse($vol);
        $base_compose[VOLUMES]['mysql-data'] = $vol_compose;
      }

      $net = file_get_contents(__DIR__ . '/../../templates/' . $dist_path . '/' . NETWORKS . '/default.yml');
      $net_compose = Yaml::parse($net);
      $base_compose[NETWORKS]['default'] = $net_compose;
    }
    return $base_compose;
  }
}