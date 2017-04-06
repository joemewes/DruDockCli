<?php

/**
 * @file
 * Contains \Docker\Drupal\Extension\DemoCommand.
 */

namespace Docker\Drupal\Extension;

use Docker\Drupal\Application;

const DEV_MYSQL_PASS = 'DEVPASSWORD';
const LOCALHOST = '127.0.0.1';

/**
 * Class ApplicationConfigExtension
 * @package Docker\Drupal\Extension
 */
class ApplicationConfigExtension extends Application {

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

    var_dump(LOCALHOST);
    var_dump(DEV_MYSQL_PASS);
    var_dump($db_name);
    var_dump($db_port);

    while (!@mysqli_connect(LOCALHOST, 'dev', DEV_MYSQL_PASS, $db_name, $db_port)) {
      sleep(1);
      echo '.';
    }

    $io->text(' ');
    $io->success('mySQL CONNECTED');
  }
}