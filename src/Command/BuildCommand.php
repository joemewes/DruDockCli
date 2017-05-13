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
 * Class BuildCommand
 *
 * @package Docker\Drupal\ContainerAwareCommand
 */
class BuildCommand extends ContainerAwareCommand {

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

  protected $app;

  protected $cfa;

  protected $cta;

  protected $fs;

  protected $io;

  protected function initialize(InputInterface $input, OutputInterface $output) {
    $this->app = new Application();
    $this->cfa = new ApplicationConfigExtension();
    $this->cta = new ApplicationContainerExtension();
    $this->io = new DruDockStyle($input, $output);
    $this->fs = new Filesystem();
  }

  protected function configure() {
    $this
      ->setName('build:init')
      ->setAliases(['init'])
      ->setDescription('Fetch and build Drupal apps')
      ->setHelp('This command will fetch and build Drupal apps')
      ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    // check if this folder is has APP config
    if (!file_exists('.config.yml')) {
      $this->io->error('You\'re not currently in an APP directory');
      return;
    }

    $apphost = self::LOCALHOST;
    $appname = 'app';

    if ($config = $this->app->getAppConfig($this->io)) {
      $appname = $config[self::APP_NAME];
      $type = $config[self::APP_TYPE];
      $apphost = $config[self::HOST];
    }

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $this->app->setNginxHost($this->io);
    if ($this->app->getOs() == 'Darwin') {
      $this->cfa->setHostConfig($apphost, $this->io, $system_appname);
    }

    /**
     * Install specific APP type
     */
    if (isset($type) && $type == 'DEFAULT') {
      $this->setUpExampleApp();
      $this->initDocker($system_appname);
      $message = 'Opening Default APP at http://' . $apphost;
    }

    if (isset($type) && $type == 'D7') {
      $this->setupD7($input, $output);
      $this->initDocker($system_appname);
      $this->installDrupal7();
      $message = 'Opening Drupal 7 base Installation at http://' . $apphost;
    }

    if (isset($type) && $type == 'D8') {
      $this->setupD8($system_appname, $input, $output);
      $this->initDocker($system_appname);
      $this->installDrupal8();
      $message = 'Opening Drupal 8 base Installation at http://' . $apphost;
    }

    $this->io->note(isset($message) ? $message : 'Opening app');
    $nginx_port = $this->cfa->containerPort($system_appname, 'nginx', '80');
    shell_exec('python -mwebbrowser http://' . $apphost . ':' . $nginx_port);
  }

  private function setupD7($input, $output) {
    $app_dest = self::APP_DEST;
    $date = date('Y-m-d--H-i-s');

    $config = $this->app->getAppConfig();
    if ($config) {
      $appsrc = $config[self::APPSRC];
      $apprepo = $config[self::REPO];
      $dist = $config[self::DIST];
    }

    if (isset($appsrc) && $appsrc == 'Git' && !$this->fs->exists($app_dest)) {
      $command = 'git clone ' . $apprepo . ' app';
      $this->app->runcommand($command, $this->io);
      $this->io->info('Downloading app from repo.... This may take a few minutes....');
      $this->io->info(' ');
      $this->io->title("SET APP DOCROOT");
      $helper = $this->getHelper('question');
      $question = new Question('Please specify repository relative path to site docroot [./web/] [./docroot/] [./] : ', './');
      $root = $helper->ask($input, $output, $question);
      $this->fs->symlink($root, $app_dest . '/www', TRUE);
    }

    if (!$this->fs->exists($app_dest)) {

      try {
        $this->fs->mkdir($app_dest);

        $this->fs->mkdir($app_dest . '/repository/libraries/custom');
        $this->fs->mkdir($app_dest . '/repository/modules/custom');
        $this->fs->mkdir($app_dest . '/repository/scripts');
        $this->fs->mkdir($app_dest . '/repository/themes/custom');

        $this->fs->mkdir($app_dest . '/shared/files');
        $this->fs->mkdir($app_dest . '/builds');

      } catch (IOExceptionInterface $e) {
        $this->io->error(sprintf(self::ERR_MSG . $e->getPath()));
      }

      $this->cfa->tmpRemoteBundle('d7');
      // Build repo content.
      if (is_dir(self::TMP_D7) && is_dir($app_dest . self::REPOSITORY)) {
        $d7files = self::TMP_D7;
        // Potential repo files.
        $this->fs->copy($d7files . self::ROBOTS_TXT, $app_dest . '/repository/robots.txt');
        $this->fs->copy($d7files . '/settings.php', $app_dest . '/repository/settings.php');
        $this->fs->copy($d7files . '/project.make.yml', $app_dest . '/repository/project.make.yml');
        $this->fs->copy($d7files . '/.gitignore', $app_dest . '/repository/.gitignore');

        // Local shared files.
        $this->fs->copy($d7files . '/settings.local.php', $app_dest . '/shared/settings.local.php');
        $this->fs->remove(self::TMP_D7);

        if (isset($dist) && $dist == 'Full') {
          $this->cfa->tmpRemoteBundle('behat');
          $this->fs->mirror(self::TMP_BEHAT, $app_dest . '/behat/');
          $this->fs->remove(self::TMP_BEHAT);
        }
      }

      //replace this with make.yml script
      $command = 'drush make ' . $app_dest . '/repository/project.make.yml ' . $app_dest . '/builds/' . $date . '/public';

      $this->io->info(' ');
      $this->io->note('Download and configure Drupal 7.... This may take a few minutes....');
      $this->app->runcommand($command, $this->io);

      $buildpath = 'builds/' . $date . '/public';
      $this->fs->symlink($buildpath, $app_dest . '/www', TRUE);

      $rel = $this->fs->makePathRelative($app_dest . '/repository/', $app_dest . '/' . $buildpath);

      $this->fs->remove([$app_dest . '/' . $buildpath . self::ROBOTS_TXT]);
      $this->fs->symlink($rel . 'robots.txt', $app_dest . '/' . $buildpath . self::ROBOTS_TXT, TRUE);

      $this->fs->remove([$app_dest . '/' . $buildpath . self::SETTINGS]);
      $this->fs->symlink('../../' . $rel . 'settings.php', $app_dest . '/' . $buildpath . self::SETTINGS, TRUE);
      $this->fs->remove([$app_dest . '/' . $buildpath . self::FILES]);
      $this->fs->symlink('../../../../../shared/settings.local.php', $app_dest . '/' . $buildpath . '/sites/default/settings.local.php', TRUE);
      $this->fs->remove([$app_dest . '/' . $buildpath . self::FILES]);
      $this->fs->symlink('../../../../../shared/files', $app_dest . '/' . $buildpath . self::FILES, TRUE);

      $this->fs->symlink($rel . '/sites/default/modules/custom', $app_dest . '/' . $buildpath . '/modules/custom', TRUE);
      $this->fs->symlink($rel . '/profiles/custom', $app_dest . '/' . $buildpath . '/profiles/custom', TRUE);
      $this->fs->symlink($rel . '/sites/default/themes/custom', $app_dest . '/' . $buildpath . '/themes/custom', TRUE);

      $this->fs->chmod($app_dest . '/' . $buildpath . self::FILES, 0777, 0000, TRUE);
      $this->fs->chmod($app_dest . '/' . $buildpath . self::SETTINGS, 0777, 0000, TRUE);
      $this->fs->chmod($app_dest . '/' . $buildpath . '/sites/default/settings.local.php', 0777, 0000, TRUE);
    }
  }

  private function setupD8($appname, $input, $output) {

    $app_dest = self::APP_DEST;
    $config = $this->app->getAppConfig($this->io);

    if ($config) {
      $appname = $config[self::APP_NAME];
      $appsrc = $config[self::APPSRC];
      $apprepo = $config[self::REPO];
      $dist = $config[self::DIST];
    }

    if (isset($appsrc) && $appsrc == 'Git' && !$this->fs->exists($app_dest) && isset($apprepo)) {
      $command = 'git clone ' . $apprepo . ' app';
      $this->app->runcommand($command, $this->io);
      $this->io->info('Downloading app from repo.... This may take a few minutes....');

      $this->io->info(' ');
      $this->io->title("SET APP DOCROOT");
      $helper = $this->getHelper('question');
      $question = new Question('Please specify repository relative path to site docroot [./web/] [./docroot/] [./] : ', './web/');
      $root = $helper->ask($input, $output, $question);
      $this->fs->symlink($root, $app_dest . '/www', TRUE);

      $command = 'cd app && composer install';
      $this->app->runcommand($command, $this->io);
    }

    if (!$this->fs->exists($app_dest)) {
      $command = sprintf('composer create-project drupal-composer/drupal-project:8.x-dev ' . $app_dest . ' -dir --stability dev --no-interaction');
      $this->io->info(' ');
      $this->io->note('Download and configure Drupal 8.... This may take a few minutes....');
      $this->app->runcommand($command, $this->io);
    }

    if ($this->fs->exists($app_dest)) {

      try {
        $this->fs->mkdir($app_dest . '/config/sync');
        $this->fs->mkdir($app_dest . '/web/sites/default/files');
        $this->fs->mkdir($app_dest . '/web/themes/custom');
        $this->fs->mkdir($app_dest . '/web/modules/custom');
        $this->fs->mkdir($app_dest . '/shared/files');

      } catch (IOExceptionInterface $e) {
        $this->io->error(sprintf(self::ERR_MSG . $e->getPath()));
      }

      if (isset($dist) && $dist === self::PROD) {
        $files_dir = 'd8prod';
      }
      else {
        $files_dir = 'd8';
      }

      $this->setD8Config($files_dir, $app_dest, $dist);

      // Set perms
      $this->fs->chmod($app_dest . '/config/sync', 0777, 0000, TRUE);
      $this->fs->chmod($app_dest . '/web/sites/default/files', 0777, 0000, TRUE);
      $this->fs->chmod($app_dest . '/web/sites/default/settings.php', 0755, 0000, TRUE);
      $this->fs->chmod($app_dest . self::SETTINGS_LOCAL, 0755, 0000, TRUE);

      // setup $VAR for redis cache_prefix in settings.local.php template
      $cache_prefix = "\$settings['cache_prefix'] = '" . $appname . "_';";
      $local_settings = $app_dest . self::SETTINGS_LOCAL;
      $process = new Process(sprintf('echo %s | sudo tee -a %s >/dev/null', $cache_prefix, $local_settings));
      $process->run();

      $this->fs->symlink('./web', $app_dest . '/www', TRUE);

    }
  }

  private function setD8Config($fd, $app_dest, $dist) {
    // Move DruDock Drupal 8 config files into install
    $this->cfa->tmpRemoteBundle($fd);
    if (is_dir(self::TMP . $fd) && is_dir($app_dest)) {
      $d8files = self::TMP . $fd;
      $this->fs->chmod($app_dest, 0755, 0000, true);

      $this->fs->copy($d8files . '/composer.json', $app_dest . '/composer.json', TRUE);
      $this->fs->copy($d8files . '/development.services.yml', $app_dest . '/web/sites/development.services.yml', TRUE);
      $this->fs->copy($d8files . '/services.yml', $app_dest . '/web/sites/default/services.yml', TRUE);
      $this->fs->copy($d8files . self::ROBOTS_TXT, $app_dest . '/web/robots.txt', TRUE);
      $this->fs->copy($d8files . '/settings.php', $app_dest . '/web/sites/default/settings.php', TRUE);
      $this->fs->copy($d8files . '/settings.local.php', $app_dest . self::SETTINGS_LOCAL, TRUE);
      $this->fs->copy($d8files . '/drushrc.php', $app_dest . '/web/sites/default/drushrc.php', TRUE);

      $this->fs->remove(self::TMP . $fd);
      if (isset($dist) && $dist == 'Full') {
        $this->cfa->tmpRemoteBundle('behat');
        $this->fs->mirror(self::TMP_BEHAT, $app_dest . '/behat/');
        $this->fs->remove(self::TMP_BEHAT);
      }
    }
  }

  private function setupExampleApp() {

    $app_dest = self::APP_DEST;

    $message = 'Setting up Example app';
    $this->io->section($message);

    $this->cfa->tmpRemoteBundle('default');
    if (is_dir(self::TMP_DEFAULT)) {
      $app_src = self::TMP_DEFAULT;
      try {
        $this->fs->mkdir($app_dest . self::REPOSITORY);
        $this->fs->mirror($app_src, $app_dest . self::REPOSITORY);
      } catch (IOExceptionInterface $e) {
        echo self::ERR_MSG . $e->getPath();
      }
      $this->fs->remove(self::TMP_DEFAULT);
      $this->fs->symlink('repository', './app/www', TRUE);
    }
  }

  private function installDrupal8() {

    if ($config = $this->app->getAppConfig($this->io)) {
      $appname = $config[self::APP_NAME];
      $services = $config[self::SERVICES];
    }

    if (isset($services) && in_array('BEHAT', $services)) {
      $command = $this->cta->getComposePath($appname, $this->io) . 'exec -T behat composer update';
      $this->app->runcommand($command, $this->io);
    }

    $message = 'Run Drupal Installation.... This may take a few minutes....';
    $this->io->note($message);
    if ($this->cta->checkForAppContainers($appname, $this->io)) {

      $command = $this->cta->getComposePath($appname, $this->io) . 'exec -T php chmod -R 755 ../vendor/';
      $this->app->runcommand($command, $this->io);

      // NB: PHP container can use internal mySQL port.
      $command = $this->cta->getComposePath($appname, $this->io) . 'exec -T php drush ' .
        'site-install standard --account-name=admin --account-pass=password --site-name=DruDock --site-mail=admin@drudock.dev ' .
        '--db-url=mysql://' . self::MYSQL_USER . ':' . self::MYSQL_PASS . '@mysql:3306/' . self::MYSQL_DB . ' --quiet -y';

      $this->app->runcommand($command, $this->io);
    }
  }

  private function installDrupal7() {

    if ($config = $this->app->getAppConfig($this->io)) {
      $appname = $config[self::APP_NAME];
    }

    $message = 'Run Drupal Installation.... This may take a few minutes....';
    $this->io->note($message);
    $system_appname = strtolower(str_replace(' ', '', $appname));

    $port = $this->cfa->containerPort($system_appname, 'mysql', '3306', FALSE);
    $command = $command = $this->cta->getComposePath($appname, $this->io) . 'exec -T php drush site-install standard --account-name=dev --account-pass=admin --site-name=DruDock --site-mail=drupalD7@drudock.dev --db-url=mysql://dev:DEVPASSWORD@mysql:' . $port . '/dev_db -y';
    $this->app->runcommand($command, $this->io);
  }

  private function initDocker($appname) {

    $system_appname = strtolower(str_replace(' ', '', $appname));

    $message = 'Creating/updating and configure DruDock app containers.... This may take a moment....';
    $this->io->note($message);

    if ($config = $this->app->getAppConfig($this->io)) {
      $dist = $config[self::DIST];

      if (is_array($config[self::BUILDS])) {
        $build = end($config[self::BUILDS]);
      }
    }

    if (isset($dist) && ($dist === self::DEVELOPMENT)) {

      if (in_array('UNISON', $config['services'])) {
        $command = 'until ' . $this->cta->getComposePath($appname, $this->io) .
          'run unison 2>&1 | grep -m 1 -e "Synchronization complete" -e "finished propagating changes" ; do : ; done ;' .
          'docker kill $(docker ps -q) 2>&1; ' . $this->cta->getComposePath($appname, $this->io) . 'up -d';
        $this->app->runcommand($command, $this->io);
      }

      $this->io->section("Docker ::: Build Development environment");
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . self::UP_CMD;
      $this->app->runcommand($command, $this->io);
    }

    // Production option specific build.
    if (isset($dist) && $dist == self::PROD) {

      $this->io->section("Docker ::: Build prod environment");

      // Setup proxy network.
      $command = self::COMPOSE . $system_appname . '/docker-compose-nginx-proxy.yml --project-name=proxy up -d';
      $this->app->runcommand($command, $this->io);

      // Setup data service.
      $command = self::COMPOSE . $system_appname . '/docker-compose-data.yml --project-name=data up -d';
      $this->app->runcommand($command, $this->io);

      // RUN APP BUILD.
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . $system_appname . '--' . $build . ' build --no-cache';
      $this->app->runcommand($command, $this->io);

      //RUN APP.
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d app';
      $this->app->runcommand($command, $this->io);

      //START PROJECT.
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . $system_appname . '--' . $build . self::UP_CMD;
      $this->app->runcommand($command, $this->io);
    }

    // Production option specific build.
    if (isset($dist) && $dist == self::UAT) {
      $this->io->section("Docker ::: Build staging environment");

      // Setup data service.
      $command = self::COMPOSE . $system_appname . '/docker-compose-data.yml --project-name=' . $system_appname . '_data up -d';
      $this->app->runcommand($command, $this->io);

      // RUN APP BUILD.
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . $system_appname . '--' . $build . ' build --no-cache';
      $this->app->runcommand($command, $this->io);

      // RUN APP.
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d app';
      $this->app->runcommand($command, $this->io);

      // START PROJECT.
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . ' --project-name=' . $system_appname . '--' . $build . UP_CMD;
      $this->io->info($command);
      $this->app->runcommand($command, $this->io);
    }

    // Feature/test option specific build.
    if (isset($dist) && $dist == self::FEATURE) {
      $this->io->section("Docker ::: Build staging environment");
      $command = self::COMPOSE . $system_appname . self::COMPOSE_PROJECT . self::UP_CMD;
      $this->app->runcommand($command, $this->io);
    }
  }

}
