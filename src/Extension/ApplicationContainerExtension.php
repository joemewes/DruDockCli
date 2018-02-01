<?php

/**
 * @file
 * Contains \Docker\Drupal\Extension\DemoCommand.
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
   * @return Boolean
   */
  public function checkForAppContainers($appname, $io) {

    $system_appname = strtolower(str_replace(' ', '', $appname));
    // Check for standard app containers
    if (exec($this->getComposePath($appname, $io) . 'ps | grep ' . preg_replace("/[^A-Za-z0-9 ]/", '', $system_appname))) {
      return TRUE;
    }
    else {
      $io->warning("APP has no containers, try running `drudock app:build --help`");
    }
  }

  /**
   * @output status table
   */
  public function dockerHealthCheck($io) {
    $names = shell_exec("echo $(docker ps --format '{{.Names}}|{{.Status}}:')");
    $n_array = explode(':', $names);
    $rows = [];
    foreach ($n_array as $i => $n) {
      $c = explode('|', $n);
      if ($c[0] && $c[1]) {
        $rows[$i]['Name'] = str_replace(' ', '', $c[0]);
        $rows[$i]['Status'] = $c[1];
      }
    }
    $headers = ['Container Name', 'Status'];
    $io->table($headers, $rows);
  }

  /**
   * @return array
   */
  public function getRunningContainerNames() {
    $names = shell_exec("echo $(docker ps --format '{{.Names}}')");
    return explode(' ', $names);
  }

  /**
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
    $command = 'docker network ls | grep drudock-frontend 2>&1 ';
    if (shell_exec($command)) {
      $io->info("FRONTEND network exists.");
    }
    else {
      $io->info("Creating frontend network.");
      $command = 'docker network create drudock-frontend';
      $this->runcommand($command, $io);
    }
  }
}