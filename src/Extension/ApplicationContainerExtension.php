<?php

/**
 * @file
 * Contains \Docker\Drupal\Extension\ApplicationContainerExtension.
 */

namespace Docker\Drupal\Extension;

use Docker\Drupal\Application;
use Symfony\Component\Filesystem\Filesystem;

const BUILDS = 'builds';

/**
 * Class ApplicationContainerExtension
 *
 * @package Docker\Drupal\Extension
 */
class ApplicationContainerExtension extends Application {

  /**
   * @param $appname
   * @param $io
   * @param $nomessage
   *
   * @return Boolean
   */
  public function checkForAppContainers($appname, $io, $nomessage = FALSE) {
    $command = $this->getComposePath($appname, $io) . 'ps | grep docker_';
    if (exec($command)) {
      return TRUE;
    }
    else {
      if($nomessage) {
        return FALSE;
      }
      $io->warning("APP has no containers, try running `drudock app:init:containers --help`");
    }
  }

  /**
   * @output status table
   *
   * @param $appname
   * @param $io
   */
  public function dockerHealthCheck($appname, $io) {
    $system_appname = strtolower(str_replace(' ', '', $appname));
    $command = $this->getComposePath($system_appname, $io) . 'ps';
    $this->runcommand($command, $io);
  }

  /**
   * @return array
   */
  public function getRunningContainerNames() {
    $names = shell_exec("echo $(docker ps --format '{{.Names}}')");
    return explode(' ', $names);
  }

  /**
   * @param $appname
   * @param $io
   *
   * @return string
   */
  public function getComposePath($appname, $io) {

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $latestbuild = [];
    $fs = new Filesystem();

    if ($config = $this->getAppConfig($io)) {
      if (isset($config['dist'])) {
        $dist = $config['dist'];
      }
      if (isset($config[BUILDS])) {
        $latestbuild = $config[BUILDS];
      }
    }

    switch ($dist) {
      case 'Production':
      case 'Staging':
        $project = '--project-name=' . $system_appname . '--' . end($latestbuild);
        break;
      default:
        $project = '';
    }

    if ($fs->exists('docker-compose.yml')) {
      return 'docker-compose ';
    }
    elseif ($fs->exists('./docker_' . $system_appname . '/docker-compose.yml')) {
      return 'docker-compose -f ./docker_' . $system_appname . '/docker-compose.yml ' . $project . ' ';
    }
    else {
      $io->error("docker-compose.yml : Not Found");
      exit;
    }
  }

  /**
   * @return string
   */
  public function getDataComposePath($appname, $io) {

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $build = [];
    $dist = '';
    $fs = new Filesystem();

    if ($config = $this->getAppConfig($io)) {
      $dist = $config['dist'];
      if (is_array($config[BUILDS])) {
        $build = end($config[BUILDS]);
      }
    }

    if (!$build) {
      $io->error('Build :: Config not found');
      return;
    }

    switch ($dist) {
      case 'Prod':
        $project = '--project-name=data';
        break;
      case 'Stage':
        $project = '--project-name=' . $system_appname . '_data';
        break;
      default:
        $project = '';
    }

    if (!$project) {
      // @todo: This message needs review.
      // @see https://github.com/4AllDigital/DruDockCli/issues/91
      $io->error("docker-compose-data.yml : Not Found");
      exit;
    }

    if ($fs->exists('./docker_' . $system_appname . '/docker-compose-data.yml')) {
      return 'docker-compose -f ./docker_' . $system_appname . '/docker-compose-data.yml ' . $project . ' ';
    }
    else {
      $io->error("docker-compose-data.yml : Not Found");
      exit;
    }
  }

  public function createProxyNetwork($io) {
    $command = 'docker network ls | grep proxy_drudock-frontend 2>&1 ';
    if (shell_exec($command)) {
      $io->info("FRONTEND network exists.");
    }
    else {
      $io->info("Creating frontend network.");
      $command = 'docker network create proxy_drudock-frontend';
      $this->runcommand($command, $io);
    }
  }
}