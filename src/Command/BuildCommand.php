<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Question\Question;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Define constants
 */

// Config constants.
const APP_NAME = 'appname';
const APP_TYPE = 'apptype';
const HOST = 'host';
const APPSRC = 'src';
const REPO = 'repo';
const DIST = 'dist';
const BUILDS = 'builds';
const SERVICES = 'services';
const PROD = 'Production';
const DEVELOPMENT = 'Development';
const FEATURE = 'Feature';
const UAT = 'UAT';

// general constants
const APP_DEST = './app';
const REPOSITORY = '/repository';
const ROBOTS_TXT = '/robots.txt';
const SETTINGS = '/sites/default/settings.php';
const SETTINGS_LOCAL = '/web/sites/default/settings.local.php';
const FILES = '/sites/default/files';
const ERR_MSG = 'An error occurred while creating your directory at ';
const COMPOSE = 'docker-compose -f docker_';
const COMPOSE_PROJECT = '/docker-compose.yml';
const UP_CMD = ' up -d';

const TMP_BEHAT = '/tmp/behat/';
const TMP_D7 = '/tmp/behat/';
const TMP = '/tmp/';
const TMP_DEFAULT = '/tmp/default';

const MYSQL_PASS = 'MYSQLPASS';
const MYSQL_USER = 'drudock';
const MYSQL_DB = 'drudock_db';
const LOCALHOST = '127.0.0.1';


/**
 * Class BuildCommand
 *
 * @package Docker\Drupal\ContainerAwareCommand
 */
class BuildCommand extends ContainerAwareCommand {

  protected $app;

  protected $cfa;

  protected $cta;

  protected function configure() {
    $this
      ->setName('build:init')
      ->setAliases(['init'])
      ->setDescription('Fetch and build Drupal apps')
      ->setHelp('This command will fetch and build Drupal apps')
      ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->app = new Application();
    $this->cfa = new ApplicationConfigExtension();
    $this->cta = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);

    // check if this folder is has APP config
    if (!file_exists('.config.yml')) {
      $io->error('You\'re not currently in an APP directory');
      return;
    }

    $fs = new Filesystem();
    $apphost = 'localhost';

    if ($config = $this->app->getAppConfig($io)) {
      $appname = $config[APP_NAME];
      $type = $config[APP_TYPE];
      $apphost = $config[HOST];
    }

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $this->app->setNginxHost($io);
    if ($this->app->getOs() == 'Darwin') {
      $this->cfa->setHostConfig($apphost, $io, $system_appname);
    }

    /**
     * Install specific APP type
     */
    if (isset($type) && $type == 'DEFAULT') {
      $this->setUpExampleApp($fs, $io);
      $this->initDocker($io, $system_appname);
      $message = 'Opening Default APP at http://' . $apphost;
    }

    if (isset($type) && $type == 'D7') {
      $this->setupD7($fs, $io, $input, $output);
      $this->initDocker($io, $system_appname);
      $this->installDrupal7($io);
      $message = 'Opening Drupal 7 base Installation at http://' . $apphost;
    }

    if (isset($type) && $type == 'D8') {
      $this->setupD8($fs, $io, $system_appname, $input, $output);
      $this->initDocker($io, $system_appname);
      $this->installDrupal8($io);
      $message = 'Opening Drupal 8 base Installation at http://' . $apphost;
    }

    $io->note($message);
    shell_exec('python -mwebbrowser http://' . $apphost);

  }

  private function setupD7($fs, $io, $input, $output) {
    $app_dest = APP_DEST;
    $date = date('Y-m-d--H-i-s');

    $config = $this->app->getAppConfig($io);
    if ($config) {
      $appsrc = $config[APPSRC];
      $apprepo = $config[REPO];
      $dist = $config[DIST];
    }

    if (isset($appsrc) && $appsrc == 'Git' && !$fs->exists($app_dest)) {
      $command = 'git clone ' . $apprepo . ' app';
      $this->app->runcommand($command, $io);
      $io->info('Downloading app from repo.... This may take a few minutes....');

      $io->info(' ');
      $io->title("SET APP DOCROOT");
      $helper = $this->getHelper('question');
      $question = new Question('Please specify repository relative path to site docroot [./web/] [./docroot/] [./] : ', './');
      $root = $helper->ask($input, $output, $question);
      $fs->symlink($root, $app_dest . '/www', TRUE);
    }

    if (!$fs->exists($app_dest)) {

      try {
        $fs->mkdir($app_dest);

        $fs->mkdir($app_dest . '/repository/libraries/custom');
        $fs->mkdir($app_dest . '/repository/modules/custom');
        $fs->mkdir($app_dest . '/repository/scripts');
        $fs->mkdir($app_dest . '/repository/themes/custom');

        $fs->mkdir($app_dest . '/shared/files');
        $fs->mkdir($app_dest . '/builds');

      } catch (IOExceptionInterface $e) {
        $io->error(sprintf(ERR_MSG . $e->getPath()));
      }

      $this->cfa->tmpRemoteBundle('d7');
      // Build repo content.
      if (is_dir(TMP_D7) && is_dir($app_dest . REPOSITORY)) {
        $d7files = TMP_D7;
        // Potential repo files.
        $fs->copy($d7files . ROBOTS_TXT, $app_dest . '/repository/robots.txt');
        $fs->copy($d7files . '/settings.php', $app_dest . '/repository/settings.php');
        $fs->copy($d7files . '/project.make.yml', $app_dest . '/repository/project.make.yml');
        $fs->copy($d7files . '/.gitignore', $app_dest . '/repository/.gitignore');

        // Local shared files.
        $fs->copy($d7files . '/settings.local.php', $app_dest . '/shared/settings.local.php');
        $fs->remove(TMP_D7);

        if (isset($dist) && $dist == 'Full') {
          $this->cfa->tmpRemoteBundle('behat');
          $fs->mirror(TMP_BEHAT, $app_dest . '/behat/');
          $fs->remove(TMP_BEHAT);
        }
      }

      //replace this with make.yml script
      $command = 'drush make ' . $app_dest . '/repository/project.make.yml ' . $app_dest . '/builds/' . $date . '/public';

      $io->info(' ');
      $io->note('Download and configure Drupal 7.... This may take a few minutes....');
      $this->app->runcommand($command, $io);

      $buildpath = 'builds/' . $date . '/public';
      $fs->symlink($buildpath, $app_dest . '/www', TRUE);

      $rel = $fs->makePathRelative($app_dest . '/repository/', $app_dest . '/' . $buildpath);

      $fs->remove([$app_dest . '/' . $buildpath . ROBOTS_TXT]);
      $fs->symlink($rel . 'robots.txt', $app_dest . '/' . $buildpath . ROBOTS_TXT, TRUE);

      $fs->remove([$app_dest . '/' . $buildpath . SETTINGS]);
      $fs->symlink('../../' . $rel . 'settings.php', $app_dest . '/' . $buildpath . SETTINGS, TRUE);
      $fs->remove([$app_dest . '/' . $buildpath . FILES]);
      $fs->symlink('../../../../../shared/settings.local.php', $app_dest . '/' . $buildpath . '/sites/default/settings.local.php', TRUE);
      $fs->remove([$app_dest . '/' . $buildpath . FILES]);
      $fs->symlink('../../../../../shared/files', $app_dest . '/' . $buildpath . FILES, TRUE);

      $fs->symlink($rel . '/sites/default/modules/custom', $app_dest . '/' . $buildpath . '/modules/custom', TRUE);
      $fs->symlink($rel . '/profiles/custom', $app_dest . '/' . $buildpath . '/profiles/custom', TRUE);
      $fs->symlink($rel . '/sites/default/themes/custom', $app_dest . '/' . $buildpath . '/themes/custom', TRUE);

      $fs->chmod($app_dest . '/' . $buildpath . FILES, 0777, 0000, TRUE);
      $fs->chmod($app_dest . '/' . $buildpath . SETTINGS, 0777, 0000, TRUE);
      $fs->chmod($app_dest . '/' . $buildpath . '/sites/default/settings.local.php', 0777, 0000, TRUE);
    }
  }

  private function setupD8($fs, $io, $appname, $input, $output) {

    $app_dest = APP_DEST;
    $config = $this->app->getAppConfig($io);

    if ($config) {
      $appname = $config[APP_NAME];
      $appsrc = $config[APPSRC];
      $apprepo = $config[REPO];
      $dist = $config[DIST];
    }

    if (isset($appsrc) && $appsrc == 'Git' && !$fs->exists($app_dest) && isset($apprepo)) {
      $command = 'git clone ' . $apprepo . ' app';
      $this->app->runcommand($command, $io);
      $io->info('Downloading app from repo.... This may take a few minutes....');

      $io->info(' ');
      $io->title("SET APP DOCROOT");
      $helper = $this->getHelper('question');
      $question = new Question('Please specify repository relative path to site docroot [./web/] [./docroot/] [./] : ', './web/');
      $root = $helper->ask($input, $output, $question);
      $fs->symlink($root, $app_dest . '/www', TRUE);

      $command = 'cd app && composer install';
      $this->app->runcommand($command, $io);
    }

    if (!$fs->exists($app_dest)) {
      $command = sprintf('composer create-project drupal-composer/drupal-project:8.x-dev ' . $app_dest . ' -dir --stability dev --no-interaction');
      $io->info(' ');
      $io->note('Download and configure Drupal 8.... This may take a few minutes....');
      $this->app->runcommand($command, $io);
    }

    if ($fs->exists($app_dest)) {

      try {
        $fs->mkdir($app_dest . '/config/sync');
        $fs->mkdir($app_dest . '/web/sites/default/files');
        $fs->mkdir($app_dest . '/web/themes/custom');
        $fs->mkdir($app_dest . '/web/modules/custom');
        $fs->mkdir($app_dest . '/shared/files');

      } catch (IOExceptionInterface $e) {
        $io->error(sprintf(ERR_MSG . $e->getPath()));
      }

      if (isset($dist) && $dist === PROD) {
        $files_dir = 'd8prod';
      }
      else {
        $files_dir = 'd8';
      }

      $this->setD8Config($fs, $files_dir, $app_dest, $dist);

      // Set perms
      $fs->chmod($app_dest . '/config/sync', 0777, 0000, TRUE);
      $fs->chmod($app_dest . '/web/sites/default/files', 0777, 0000, TRUE);
      $fs->chmod($app_dest . '/web/sites/default/settings.php', 0755, 0000, TRUE);
      $fs->chmod($app_dest . SETTINGS_LOCAL, 0755, 0000, TRUE);

      // setup $VAR for redis cache_prefix in settings.local.php template
      $cache_prefix = "\$settings['cache_prefix'] = '" . $appname . "_';";
      $local_settings = $app_dest . SETTINGS_LOCAL;
      $process = new Process(sprintf('echo %s | sudo tee -a %s >/dev/null', $cache_prefix, $local_settings));
      $process->run();

      $fs->symlink('./web', $app_dest . '/www', TRUE);

    }
  }

  private function setD8Config($fs, $fd, $app_dest, $dist) {
    // Move DruDock Drupal 8 config files into install
    $this->cfa->tmpRemoteBundle($fd);
    if (is_dir(TMP . $fd) && is_dir($app_dest)) {
      $d8files = TMP . $fd;

      $fs->copy($d8files . '/composer.json', $app_dest . '/composer.json', TRUE);
      $fs->copy($d8files . '/development.services.yml', $app_dest . '/web/sites/development.services.yml', TRUE);
      $fs->copy($d8files . '/services.yml', $app_dest . '/web/sites/default/services.yml', TRUE);
      $fs->copy($d8files . ROBOTS_TXT, $app_dest . '/web/robots.txt', TRUE);
      $fs->copy($d8files . '/settings.php', $app_dest . '/web/sites/default/settings.php', TRUE);
      $fs->copy($d8files . '/settings.local.php', $app_dest . SETTINGS_LOCAL, TRUE);
      $fs->copy($d8files . '/drushrc.php', $app_dest . '/web/sites/default/drushrc.php', TRUE);

      $fs->remove(TMP . $fd);
      if (isset($dist) && $dist == 'Full') {
        $this->cfa->tmpRemoteBundle('behat');
        $fs->mirror(TMP_BEHAT, $app_dest . '/behat/');
        $fs->remove(TMP_BEHAT);
      }
    }
  }

  private function setupExampleApp($fs, $io) {

    $app_dest = APP_DEST;

    $message = 'Setting up Example app';
    $io->section($message);

    $this->cfa->tmpRemoteBundle('default');
    if (is_dir(TMP_DEFAULT)) {
      $app_src = TMP_DEFAULT;
      try {
        $fs->mkdir($app_dest . REPOSITORY);
        $fs->mirror($app_src, $app_dest . REPOSITORY);
      } catch (IOExceptionInterface $e) {
        echo ERR_MSG . $e->getPath();
      }
      $fs->remove(TMP_DEFAULT);
      $fs->symlink('repository', './app/www', TRUE);
    }
  }

  private function installDrupal8($io, $install_helpers = FALSE) {

    if ($config = $this->app->getAppConfig($io)) {
      $dist = $config[DIST];
      $appname = $config[APP_NAME];
      $services = $config[SERVICES];
    }

    if (isset($services) && in_array('BEHAT', $services)) {
      $command = $this->cta->getComposePath($appname, $io) . 'exec -T behat composer update';
      $this->app->runcommand($command, $io);
    }

    $message = 'Run Drupal Installation.... This may take a few minutes....';
    $io->note($message);
    if ($this->cta->checkForAppContainers($appname, $io)) {

      $command = $this->cta->getComposePath($appname, $io) . 'exec -T php chmod -R 755 ../vendor/';
      $this->app->runcommand($command, $io);

      $command = $this->cta->getComposePath($appname, $io) . 'exec -T php drush site-install standard --account-name=admin --account-pass=password --site-name=DruDock --site-mail=admin@drudock.dev --db-url=mysql://' . MYSQL_USER . ':' . MYSQL_PASS . '@mysql:3306/' . MYSQL_DB . ' --quiet -y';
      $this->app->runcommand($command, $io);

      if ($install_helpers) {
        $message = 'Run APP composer update';
        $io->note($message);

        $command = $this->cta->getComposePath($appname, $io) . 'exec -T php composer update';
        $this->app->runcommand($command, $io);

        $message = 'Enable useful starter contrib modules';
        $io->note($message);

        $command = $this->cta->getComposePath($appname, $io) . 'exec -T php drush en admin_toolbar ctools redis token adminimal_admin_toolbar devel pathauto webprofiler -y';
        $this->app->runcommand($command, $io);

        $command = $this->cta->getComposePath($appname, $io) . 'exec -T php drush entity-updates -y';
        $this->app->runcommand($command, $io);
      }
    }
  }

  private function installDrupal7($io) {

    if ($config = $this->app->getAppConfig($io)) {
      $appname = $config[APP_NAME];
    }

    $message = 'Run Drupal Installation.... This may take a few minutes....';
    $io->note($message);
    $system_appname = strtolower(str_replace(' ', '', $appname));

    $port = $this->cfa->containerPort($system_appname, 'mysql', '3306', FALSE);
    $command = $command = $this->cta->getComposePath($appname, $io) . 'exec -T php drush site-install standard --account-name=dev --account-pass=admin --site-name=DruDock --site-mail=drupalD7@drudock.dev --db-url=mysql://dev:DEVPASSWORD@mysql:' . $port . '/dev_db -y';
    $this->app->runcommand($command, $io);
  }

  private function initDocker($io, $appname) {

    $system_appname = strtolower(str_replace(' ', '', $appname));

    $message = 'Creating/updating and configure DruDock app containers.... This may take a moment....';
    $io->note($message);

    if ($config = $this->app->getAppConfig($io)) {
      $dist = $config[DIST];

      if (is_array($config[BUILDS])) {
        $build = end($config[BUILDS]);
      }
    }

    if (isset($dist) && ($dist === DEVELOPMENT)) {

      if (in_array('UNISON', $config['services'])) {
        // Run Unison APP SYNC so that PHP working directory is ready to go with DATA stored in the Docker Volume.
        // When 'Synchronization complete' kill this temp run container and start DruDock.
        $command = 'until ' . $this->cta->getComposePath($appname, $io) .
          'run unison 2>&1 | grep -m 1 -e "Synchronization complete" -e "finished propagating changes" ; do : ; done ;' .
          'docker kill $(docker ps -q) 2>&1; ' . $this->cta->getComposePath($appname, $io) . 'up -d';
        $this->app->runcommand($command, $io);
      }
      if (in_array('MYSQL', $config['services'])) {
        $this->cfa->verifyMySQL($io, $system_appname, 'default');
      }
    }

    // Production option specific build.
    if (isset($dist) && $dist == PROD) {

      $io->section("Docker ::: Build prod environment");

      // Setup proxy network.
      $command = COMPOSE . $system_appname . '/docker-compose-nginx-proxy.yml --project-name=proxy up -d';
      $this->app->runcommand($command, $io);

      // Setup data service.
      $command = COMPOSE . $system_appname . '/docker-compose-data.yml --project-name=data up -d';
      $this->app->runcommand($command, $io);

      // RUN APP BUILD.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' build --no-cache';
      $this->app->runcommand($command, $io);

      //RUN APP.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d app';
      $this->app->runcommand($command, $io);

      //START PROJECT.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . UP_CMD;
      $this->app->runcommand($command, $io);

      if (in_array('MYSQL', $config['services'])) {
        $this->cfa->verifyMySQL($io, $system_appname, 'prod');
      }

    }

    // Production option specific build.
    if (isset($dist) && $dist == UAT) {
      $io->section("Docker ::: Build staging environment");

      // Setup data service.
      $command = COMPOSE . $system_appname . '/docker-compose-data.yml --project-name=' . $system_appname . '_data up -d';
      $this->app->runcommand($command, $io);

      // RUN APP BUILD.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' build --no-cache';
      $this->app->runcommand($command, $io);

      // RUN APP.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d app';
      $this->app->runcommand($command, $io);

      // START PROJECT.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . ' --project-name=' . $system_appname . '--' . $build . UP_CMD;
      $io->info($command);
      $this->app->runcommand($command, $io);

//      if (in_array('MYSQL', $config['services'])) {
//        $this->cfa->verifyMySQL($io, $system_appname, 'feature');
//      }
    }

    // Feature/test option specific build.
    if (isset($dist) && $dist == FEATURE) {
      $io->section("Docker ::: Build staging environment");
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . UP_CMD;
      $this->app->runcommand($command, $io);
//      if (in_array('MYSQL', $config['services'])) {
//        $this->cfa->verifyMySQL($io, $system_appname, 'feature');
//      }
    }
  }
}
