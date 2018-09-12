<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\BuildCommand.
 */

namespace Docker\Drupal\Command\App;

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
class BuildCommand extends ContainerAwareCommand
{

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

    const WEBROOT = 'webroot';

  // General constants.
    const APP_DEST = './app';

    const REPOSITORY = '/repository';

    const ROBOTS_TXT = '/robots.txt';

    const SETTINGS = '/sites/default/settings.php';

    const SETTINGS_LOCAL = '/sites/default/settings.local.php';

    const FILES = '/sites/default/files';

    const ERR_MSG = 'An error occurred while creating your directory at ';

    const COMPOSE = 'docker-compose -f docker_';

    const COMPOSE_PROJECT = '/docker-compose.yml';

    const UP_CMD = ' up -d';

    const TMP_BEHAT = '/tmp/behat/';

    const TMP_D7 = '/tmp/d7/';

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

    public function __construct()
    {
        $this->app = new Application();
        $this->cfa = new ApplicationConfigExtension();
        $this->cta = new ApplicationContainerExtension();
        $this->fs = new Filesystem();
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new DruDockStyle($input, $output);
    }

    protected function configure()
    {
        $this
        ->setName('app:build')
        ->setAliases(['ab'])
        ->setDescription('Fetch and build App containers and resources.')
        ->setHelp('This command will fetch and build Drupal apps')
        ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
            $this->setupDrupal($input, $output, '7.x-dev', 'd7');
            $this->initDocker($system_appname);
            $this->installDrupal7();
            $message = 'Opening Drupal 7 base Installation at http://' . $apphost;
        }

        if (isset($type) && $type == 'D8') {
            $this->setupDrupal($input, $output, '8.x-dev', 'd8');
            $this->initDocker($system_appname);
            $this->installDrupal8();
            $message = 'Opening Drupal 8 base Installation at http://' . $apphost;
        }

        $this->io->note(isset($message) ? $message : 'Opening app');
        $nginx_port = $this->cfa->containerPort($system_appname, 'nginx', '80');
        shell_exec('python -mwebbrowser http://' . $apphost . ':' . $nginx_port);
    }

    private function setupDrupal($input, $output, $drupal_version, $type)
    {
        $app_dest = self::APP_DEST;
        $config = $this->app->getAppConfig($this->io);

        if ($config) {
            $appname = $config[self::APP_NAME];
            $appsrc = $config[self::APPSRC];
            $apprepo = $config[self::REPO];
        }

        if (isset($appsrc) && $appsrc == 'Git' && !$this->fs->exists($app_dest) && isset($apprepo)) {
            $command = 'git clone ' . $apprepo . ' app';
            $this->app->runcommand($command, $this->io);
            $this->io->info('Downloading app from repo.... This may take a few minutes....');

            $this->io->info(' ');
            $this->io->title("SET APP DOCROOT");
            $helper = $this->getHelper('question');
            $question = new Question('Please specify Drupal root. eg. [web] [docroot] [.] : ', 'web');
            $root = $helper->ask($input, $output, $question);
            $this->fs->symlink($root, $app_dest . '/www');

          // Update config to include webroot for future use.
            $config[self::WEBROOT] = $root;
            $this->app->setAppConfig($config, $this->io);

            if ($this->fs->exists('./app/composer.json')) {
                $command = 'cd app && composer install';
                $this->app->runcommand($command, $this->io);
            }
        } else {
            $command = sprintf('composer create-project drupal-composer/drupal-project:' . $drupal_version . ' ' . $app_dest . ' -dir --stability dev --no-interaction');
            $this->io->info(' ');
            $this->io->note('Download and configure Drupal ' . $drupal_version . '.... This may take a few minutes....');
            $this->app->runcommand($command, $this->io);

          // Update config to include webroot for future use.
            $config[self::WEBROOT] = 'web';
            $this->app->setAppConfig($config, $this->io);
        }

      // Create site install directory structure.
        if ($this->fs->exists($app_dest)) {
            try {
                if ($type === 'd7') {
                    $this->fs->mkdir($app_dest . '/' . $config[self::WEBROOT] . '/sites/all/themes/custom');
                    $this->fs->mkdir($app_dest . '/' . $config[self::WEBROOT] . '/sites/all/modules/custom');
                    $this->fs->mkdir($app_dest . '/' . $config[self::WEBROOT] . '/sites/all/features/');
                }
                if ($type === 'd8') {
                    $this->fs->mkdir($app_dest . '/' . $config[self::WEBROOT] . '/themes/custom');
                    $this->fs->mkdir($app_dest . '/' . $config[self::WEBROOT] . '/modules/custom');
                    $this->fs->mkdir($app_dest . '/config/sync');
                }
            } catch (IOExceptionInterface $e) {
                $this->io->error(sprintf(self::ERR_MSG . $e->getPath()));
            }

          // Setup local configuration files.
            $this->setLocalConfig($type, $app_dest, $config);

            if ($type === 'd8') {
                $this->fs->mkdir($app_dest . '/config/sync');
            }

          // Setup $VAR for redis cache_prefix in settings.local.php template.
            $cache_prefix = "\$settings['cache_prefix'] = '" . $appname . "_';";
            $local_settings = $app_dest . '/' . $config[self::WEBROOT] . '/' . self::SETTINGS_LOCAL;
            $process = new Process(sprintf('echo %s | sudo tee -a %s >/dev/null', $cache_prefix, $local_settings));
            $process->run();

            $this->fs->symlink('./' . $config[self::WEBROOT], $app_dest . '/www', true);
        }
    }

    private function setLocalConfig($fd, $app_dest, $config)
    {
        $dist = $config[self::DIST];

      // Move DruDock Drupal config files into install.
        $this->cfa->tmpRemoteBundle($fd);
        if (is_dir(self::TMP . $fd) && is_dir($app_dest)) {
            $dfiles = self::TMP . $fd;

            if ($fd === 'd8') {
                $this->fs->copy($dfiles . '/development.services.yml', $app_dest . '/' . $config[self::WEBROOT] . '/sites/development.services.yml', true);
                $this->fs->copy($dfiles . '/services.yml', $app_dest . '/' . $config[self::WEBROOT] . '/sites/default/services.yml', true);
            }

            $this->fs->copy($dfiles . self::ROBOTS_TXT, $app_dest . '/' . $config[self::WEBROOT] . '/robots.txt', true);
            $this->fs->copy($dfiles . '/settings.php', $app_dest . '/' . $config[self::WEBROOT] . self::SETTINGS, true);
            $this->fs->copy($dfiles . '/settings.local.php', $app_dest . '/' . $config[self::WEBROOT] . self::SETTINGS_LOCAL, true);

            $this->fs->remove(self::TMP . $fd);
            if (isset($dist) && $dist == 'Full') {
                $this->cfa->tmpRemoteBundle('behat');
                $this->fs->mirror(self::TMP_BEHAT, $app_dest . '/behat/');
                $this->fs->remove(self::TMP_BEHAT);
            }
        }
    }

    private function setupExampleApp()
    {

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
            $this->fs->symlink('repository', './app/www', true);
        }
    }

    private function installDrupal8()
    {

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
            'site-install standard --account-name=admin --account-pass=password --site-name=DruDock --site-mail=admin@drudock.localhost ' .
            '--db-url=mysql://' . self::MYSQL_USER . ':' . self::MYSQL_PASS . '@mysql:3306/' . self::MYSQL_DB . ' --quiet -y';

            $this->app->runcommand($command, $this->io);
        }
    }

    private function installDrupal7()
    {

        if ($config = $this->app->getAppConfig($this->io)) {
            $appname = $config[self::APP_NAME];
        }

        $message = 'Run Drupal Installation.... This may take a few minutes....';
        $this->io->note($message);
        $command = $this->cta->getComposePath($appname, $this->io) . 'exec -T php ' .
        '/usr/bin/env PHP_OPTIONS="-d sendmail_path=/bin/true" ' .
        'drush site-install standard --account-name=admin --account-pass=password --site-name=DruDock --site-mail=admin@drudock.localhost ' .
        '--db-url=mysql://' . self::MYSQL_USER . ':' . self::MYSQL_PASS . '@mysql:3306/' . self::MYSQL_DB . ' --quiet -y';
        $this->app->runcommand($command, $this->io);
    }

    private function initDocker($appname)
    {

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

      // UAT option specific build.
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
