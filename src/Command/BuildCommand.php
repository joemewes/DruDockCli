<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Question\Question;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DockerDrupalStyle;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Alchemy\Zippy\Zippy;
use GuzzleHttp\Client;

/**
 * Define constants
 */

// Config constants.
const APP_NAME = 'appname';
const APPSRC = 'appsrc';
const REPO = 'repo';
const REQS = 'reqs';
const BUILDS = 'builds';

// general constants
const APP_DEST = './app';
const REPOSITORY = '/repository';
const ROBOTS_TXT = '/robots.txt';
const SETTINGS = '/sites/default/settings.php';
const SETTINGS_LOCAL = '/web/sites/default/settings.local.php';
const FILES = '/sites/default/files';
const ERR_MSG = 'An error occurred while creating your directory at ';
const COMPOSE = 'docker-compose -f docker_';
const COMPOSE_PROJECT = '/docker-compose.yml --project-name=';

const TMP_BEHAT = '/tmp/behat/';
const TMP_D7 = '/tmp/behat/';
const TMP = '/tmp/';
const TMP_DEFAULT = '/tmp/default';


/**
 * Class DemoCommand
 * @package Docker\Drupal\ContainerAwareCommand
 */
class BuildCommand extends ContainerAwareCommand {

  protected function configure() {
    $this
      ->setName('build:init')
      ->setAliases(['init'])
      ->setDescription('Fetch and build Drupal apps')
      ->setHelp('This command will fetch and build Drupal apps')
      ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $config_application = new ApplicationConfigExtension();

    $io = new DockerDrupalStyle($input, $output);

    // check if this folder is has APP config
    if (!file_exists('.config.yml')) {
      $io->error('You\'re not currently in an APP directory');
      return;
    }

    $fs = new Filesystem();
    $client = new Client();
    $zippy = Zippy::load();

    $config = $application->getAppConfig($io);
    if ($config) {
      if (!$config[APP_NAME]) {
        $application->requireUpdate($io);
      }
      else {
        $appname = $config[APP_NAME];
      }

      if (!$config['apptype']) {
        $application->requireUpdate($io);
      }
      else {
        $type = $config['apptype'];
      }

      if (!$config['host']) {
        $application->requireUpdate($io);
      }
      else {
        $apphost = $config['host'];
      }

    }

    $system_appname = strtolower(str_replace(' ', '', $appname));

    $application->setNginxHost($io);

    if ($application->getOs() == 'Darwin') {
      $application->addHostConfig($fs, $client, $zippy, $apphost, $io, TRUE);
    }

    /**
     * Install specific APP type
     */
    if (isset($type) && $type == 'DEFAULT') {
      $this->setUpExampleApp($fs, $io, $client, $zippy);
      $this->initDocker($io, $system_appname, $config_application);
      $message = 'Opening Default APP at http://' . $apphost;
    }
    if (isset($type) && $type == 'D7') {
      $this->setupD7($fs, $io, $input, $output, $client, $zippy);
      $this->initDocker($io, $system_appname, $config_application);
      $this->installDrupal7($io);
      $message = 'Opening Drupal 7 base Installation at http://' . $apphost;
    }
    if (isset($type) && $type == 'D8') {
      $this->setupD8($fs, $io, $system_appname, $input, $output, $client, $zippy);
      $this->initDocker($io, $system_appname, $config_application);
      $this->installDrupal8($io);
      $message = 'Opening Drupal 8 base Installation at http://' . $apphost;
    }

    $io->note($message);
    shell_exec('python -mwebbrowser http://' . $apphost);

  }

  private function setupD7($fs, $io, $input, $output, $client, $zippy) {
    $app_dest = APP_DEST;
    $date = date('Y-m-d--H-i-s');

    $application = $this->getApplication();

    $config = $application->getAppConfig($io);
    if ($config) {
      $appsrc = $config[APPSRC];
      $apprepo = $config[REPO];
      $reqs = $config[REQS];
    }

    if (isset($appsrc) && $appsrc == 'Git' && !$fs->exists($app_dest)) {
      $command = 'git clone ' . $apprepo . ' app';
      $application->runcommand($command, $io);
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


      $application->tmpRemoteBundle($fs, $client, $zippy, 'd7');
      // Build repo content.
      if (is_dir(TMP_D7) && is_dir($app_dest . REPOSITORY)) {
        $d7files = TMP_D7;
        // Potential repo files.
        $fs->copy($d7files . ROBOT_TXT, $app_dest . '/repository/robots.txt');
        $fs->copy($d7files . '/settings.php', $app_dest . '/repository/settings.php');
        $fs->copy($d7files . '/project.make.yml', $app_dest . '/repository/project.make.yml');
        $fs->copy($d7files . '/.gitignore', $app_dest . '/repository/.gitignore');

        // Local shared files.
        $fs->copy($d7files . '/settings.local.php', $app_dest . '/shared/settings.local.php');
        $fs->remove(TMP_D7);

        if (isset($reqs) && $reqs == 'Full') {
          $application->tmpRemoteBundle($fs, $client, $zippy, 'behat');
          $fs->mirror(TMP_BEHAT, $app_dest . '/behat/');
          $fs->remove(TMP_BEHAT);
        }
      }

      //replace this with make.yml script
      $command = 'drush make ' . $app_dest . '/repository/project.make.yml ' . $app_dest . '/builds/' . $date . '/public';

      $io->info(' ');
      $io->note('Download and configure Drupal 7.... This may take a few minutes....');
      $application->runcommand($command, $io);

      $buildpath = 'builds/' . $date . '/public';
      $fs->symlink($buildpath, $app_dest . '/www', TRUE);

      $rel = $fs->makePathRelative($app_dest . '/repository/', $app_dest . '/' . $buildpath);

      $fs->remove([$app_dest . '/' . $buildpath . ROBOT_TXT]);
      $fs->symlink($rel . 'robots.txt', $app_dest . '/' . $buildpath . ROBOT_TXT, TRUE);

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

  private function setupD8($fs, $io, $appname, $input, $output, $client, $zippy) {

    $app_dest = APP_DEST;
    $application = $this->getApplication();

    $config = $application->getAppConfig($io);
    if ($config) {
      $appname = $config[APP_NAME];
      $appsrc = $config[APPSRC];
      $apprepo = $config[REPO];
      $reqs = $config[REQS];
    }

    if (isset($appsrc) && $appsrc == 'Git' && !$fs->exists($app_dest)) {
      $command = 'git clone ' . $apprepo . ' app';
      $application->runcommand($command, $io);
      $io->info('Downloading app from repo.... This may take a few minutes....');

      $io->info(' ');
      $io->title("SET APP DOCROOT");
      $helper = $this->getHelper('question');
      $question = new Question('Please specify repository relative path to site docroot [./web/] [./docroot/] [./] : ', './web/');
      $root = $helper->ask($input, $output, $question);
      $fs->symlink($root, $app_dest . '/www', TRUE);

      $command = 'cd app && composer install';
      $application->runcommand($command, $io);
    }

    if (!$fs->exists($app_dest)) {
      $command = sprintf('composer create-project drupal-composer/drupal-project:8.x-dev ' . $app_dest . ' -dir --stability dev --no-interaction');
      $io->info(' ');
      $io->note('Download and configure Drupal 8.... This may take a few minutes....');
      $application->runcommand($command, $io);
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

      if ($reqs == 'Prod') {
        $files_dir = 'd8prod';
      }
      else {
        $files_dir = 'd8';
      }

      // Move DockerDrupal Drupal 8 config files into install
      $application->tmpRemoteBundle($fs, $client, $zippy, $files_dir);
      if (is_dir(TMP . $files_dir) && is_dir($app_dest)) {
        $d8files = TMP . $files_dir;

        $fs->copy($d8files . '/composer.json', $app_dest . '/composer.json', TRUE);
        $fs->copy($d8files . '/development.services.yml', $app_dest . '/web/sites/development.services.yml', TRUE);
        $fs->copy($d8files . '/services.yml', $app_dest . '/web/sites/default/services.yml', TRUE);
        $fs->copy($d8files . ROBOT_TXT, $app_dest . '/web/robots.txt', TRUE);
        $fs->copy($d8files . '/settings.php', $app_dest . '/web/sites/default/settings.php', TRUE);
        $fs->copy($d8files . '/settings.local.php', $app_dest . SETTINGS_LOCAL, TRUE);
        $fs->copy($d8files . '/drushrc.php', $app_dest . '/web/sites/default/drushrc.php', TRUE);

        $fs->remove(TMP . $files_dir);
        if (isset($reqs) && $reqs == 'Full') {
          $application->tmpRemoteBundle($fs, $client, $zippy, 'behat');
          $fs->mirror(TMP_BEHAT, $app_dest . '/behat/');
          $fs->remove(TMP_BEHAT);
        }
      }

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

  private function setupExampleApp($fs, $io, $client, $zippy) {

    $app_dest = APP_DEST;
    $application = $this->getApplication();

    $message = 'Setting up Example app';
    $io->section($message);

    $application->tmpRemoteBundle($fs, $client, $zippy, 'default');
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

    $application = $this->getApplication();
    $container_application = new ApplicationContainerExtension();

    if ($config = $application->getAppConfig($io)) {
      $reqs = $config[REQS];
      $appname = $config[APP_NAME];
    }

    if (isset($reqs) && $reqs == 'Full') {
      $command = $application->getComposePath($appname, $io) . 'exec -T behat composer update';
      $application->runcommand($command, $io);
    }

    $message = 'Run Drupal Installation.... This may take a few minutes....';
    $io->note($message);
    if ($container_application->checkForAppContainers($appname, $io)) {

      if ($reqs == 'Basic' || $reqs == 'Full') {
        $command = $application->getComposePath($appname, $io) . 'exec -T php chmod -R 777 ../vendor/';
        $application->runcommand($command, $io);

        $command = $application->getComposePath($appname, $io) . 'exec -T php drush site-install standard --account-name=dev --account-pass=admin --site-name=DockerDrupal --site-mail=drupalD8@docker.dev --db-url=mysql://dev:DEVPASSWORD@db:3306/dev_db --quiet -y';
        $application->runcommand($command, $io);
      }
      if ($reqs == 'Prod') {
        $command = $application->getComposePath($appname, $io) . 'exec -T php drush site-install standard --account-name=prod --account-pass=admin --site-name=DockerDrupal --site-mail=drupalD8@docker.prod --db-url=mysql://dev:DRUPALPASSENV@db:3306/prod --quiet -y';
        $application->runcommand($command, $io);
      }
      if ($reqs == 'Stage') {
        $command = $application->getComposePath($appname, $io) . 'exec -T php drush site-install standard --account-name=stage --account-pass=admin --site-name=DockerDrupal --site-mail=drupalD8@docker.prod --db-url=mysql://dev:DRUPALPASSENV@db:3306/prod --quiet -y';
        $application->runcommand($command, $io);
      }

      if ($install_helpers) {
        $message = 'Run APP composer update';
        $io->note($message);

        $command = $application->getComposePath($appname, $io) . 'exec -T php composer update';
        $application->runcommand($command, $io);

        $message = 'Enable useful starter contrib modules';
        $io->note($message);

        $command = $application->getComposePath($appname, $io) . 'exec -T php drush en admin_toolbar ctools redis token adminimal_admin_toolbar devel pathauto webprofiler -y';
        $application->runcommand($command, $io);

        $command = $application->getComposePath($appname, $io) . 'exec -T php drush entity-updates -y';
        $application->runcommand($command, $io);
      }
    }
  }

  private function installDrupal7($io) {

    $application = $this->getApplication();
    if ($config = $application->getAppConfig($io)) {
      $appname = $config[APP_NAME];
    }

    $message = 'Run Drupal Installation.... This may take a few minutes....';
    $io->note($message);

    $command = $command = $application->getComposePath($appname, $io) . 'exec -T php drush site-install standard --account-name=dev --account-pass=admin --site-name=DockerDrupal --site-mail=drupalD7@docker.dev --db-url=mysql://dev:DEVPASSWORD@db:3306/dev_db -y';
    $application->runcommand($command, $io);
  }

  private function initDocker($io, $appname, $config_application) {

    $application = $this->getApplication();
    $system_appname = strtolower(str_replace(' ', '', $appname));

    $message = 'Creating and configure DockerDrupal containers.... This may take a moment....';
    $io->note($message);

    if ($config = $application->getAppConfig($io)) {
      $appreqs = $config[REQS];
      if (is_array($config[BUILDS])) {
        $build = end($config[BUILDS]);
      }
    }

    if (isset($appreqs) && ($appreqs == 'Basic' || $appreqs == 'Full')) {
      // Run Unison APP SYNC so that PHP working directory is ready to go with DATA stored in the Docker Volume.
      // When 'Synchronization complete' kill this temp run container and start DockerDrupal.
      $command = 'until ' . $application->getComposePath($appname, $io) .
        'run app 2>&1 | grep -m 1 -e "Synchronization complete" -e "finished propagating changes" ; do : ; done ;' .
        'docker kill $(docker ps -q) 2>&1; ' . $application->getComposePath($appname, $io) . 'up -d';

      $application->runcommand($command, $io);
      $config_application->verifyMySQL($io, $system_appname, 'default');
    }

    // Production option specific build.
    if (isset($appreqs) && $appreqs == 'Prod') {

      $io->section("Docker ::: Build prod environment");

      // Setup proxy network.
      $command = COMPOSE . $system_appname . '/docker-compose-nginx-proxy.yml --project-name=proxy up -d';
      $application->runcommand($command, $io);

      // Setup data service.
      $command = COMPOSE . $system_appname . '/docker-compose-data.yml --project-name=data up -d';
      $application->runcommand($command, $io);

      // RUN APP BUILD.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' build --no-cache';
      $application->runcommand($command, $io);

      //RUN APP.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d app';
      $application->runcommand($command, $io);

      //START PROJECT.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d';
      $application->runcommand($command, $io);

      $config_application->verifyMySQL($io, $system_appname, 'prod');

    }

    // Production option specific build.
    if (isset($appreqs) && $appreqs == 'Stage') {
      $io->section("Docker ::: Build staging environment");

      // Setup data service.
      $command = COMPOSE . $system_appname . '/docker-compose-data.yml --project-name=' . $system_appname . '_data up -d';
      $application->runcommand($command, $io);

      // RUN APP BUILD.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' build --no-cache';
      $application->runcommand($command, $io);

      //RUN APP.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d app';
      $application->runcommand($command, $io);

      //START PROJECT.
      $command = COMPOSE . $system_appname . COMPOSE_PROJECT . $system_appname . '--' . $build . ' up -d';
      $application->runcommand($command, $io);

      $config_application->verifyMySQL($io, $system_appname, 'stage');
    }
  }
}
