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
 * Class ApplicationContainerExtension
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
      $io->warning("APP has no containers, try running `dockerdrupal build:init --help`");
    }
  }
}