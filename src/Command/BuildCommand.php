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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DockerDrupalStyle;


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
			//->addArgument('appname', InputArgument::OPTIONAL, 'Specify NAME of application to build [app-dd-mm-YYYY]')
			->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$application = $this->getApplication();
		$io = new DockerDrupalStyle($input, $output);

		// check if this folder is has APP config
		if (!file_exists('.config.yml')) {
			$io->error('You\'re not currently in an APP directory');
			return;
		}

		// check Docker is running
		$application->checkDocker($io, TRUE);

		$fs = new Filesystem();
		$date = date('Y-m-d--H-i-s');

		$config = $application->getAppConfig($io);
		if ($config) {
			$appname = $config['appname'];
			$type = $config['apptype'];
		}

		$system_appname = strtolower(str_replace(' ', '', $appname));

		/**
		 * Install specific APP type
		 */
		if (isset($type) && $type == 'DEFAULT') {
			$this->setUpExampleApp($fs, $io, $system_appname);
			$this->initDocker($io, $system_appname);
			$message = 'Opening Default APP at http://docker.dev';
		}
		if (isset($type) && $type == 'D7') {
			$this->setupD7($fs, $io, $system_appname);
			$this->initDocker($io, $system_appname);
			$this->installDrupal7($io);
			$message = 'Opening Drupal 7 base Installation at http://docker.dev';
		}
		if (isset($type) && $type == 'D8') {
			$this->setupD8($fs, $io, $system_appname);
			$this->initDocker($io, $system_appname);
			$this->installDrupal8($io);
			$message = 'Opening Drupal 8 base Installation at http://docker.dev';
		}

		$io->note($message);
		shell_exec('python -mwebbrowser http://docker.dev');

	}

	protected function runcommand($command, $io, $showoutput = TRUE) {

		global $output;
		$output = $io;

		$process = new Process($command);
		$process->setTimeout(3600);
		$process->run(function ($type, $buffer) {
			global $output;
			if ($output) {
				$output->info($buffer);
			}
		});

		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
	}

	private function initDocker($io, $appname) {

		if (exec('docker ps -q 2>&1', $exec_output)) {
			$dockerstopcmd = 'docker stop $(docker ps -q)';
			$this->runcommand($dockerstopcmd, $io, TRUE);
		}

		$message = 'Creating and configure DockerDrupal containers.... This may take a moment....';
		$io->note($message);

		// Run Unison APP SYNC so that PHP working directory is ready to go with DATA stored in the Docker Volume.
		// When 'Synchronization complete' kill this temp run container and start DockerDrupal.
		// $dockerlogs = 'docker-compose -f '.$appname.'/docker_'.$appname.'/docker-compose.yml logs -f';
		// $this->runcommand($dockerlogs, $io, TRUE);

		$dockercmd = 'until docker-compose -f ./docker_' . $appname . '/docker-compose.yml run app 2>&1 | grep -m 1 -e "Synchronization complete" -e "Nothing to do:" ; do : ; done';
		$this->runcommand($dockercmd, $io, TRUE);

		$dockercmd = 'docker kill $(docker ps -q)';
		$this->runcommand($dockercmd, $io, TRUE);

		$dockercmd = 'docker-compose -f ./docker_' . $appname . '/docker-compose.yml up -d';
		$this->runcommand($dockercmd, $io, TRUE);

		// Check for running mySQL container before launching Drupal Installation
		$message = 'Waiting for mySQL service.';
		$io->warning($message);
		while (!@mysqli_connect('127.0.0.1', 'dev', 'DEVPASSWORD', 'dev_db')) {
			sleep(1);
			echo '.';
		}
		$io->text(' ');
		$message = 'mySQL CONNECTED';
		$io->success($message);

	}

	private function setupD7($fs, $io, $appname) {
		$app_dest = './app';
		$date = date('Y-m-d--H-i-s');

		$application = $this->getApplication();
		$utilRoot = $application->getUtilRoot();

    $config = $application->getAppConfig($io);
    if ($config) {
      $appname = $config['appname'];
      $type = $config['apptype'];
      $reqs = $config['reqs'];
    }

		if(!$fs->exists($app_dest)) {

			try {
				$fs->mkdir($app_dest);

				$fs->mkdir($app_dest . '/repository/libraries/custom');
				$fs->mkdir($app_dest . '/repository/modules/custom');
				$fs->mkdir($app_dest . '/repository/scripts');
				$fs->mkdir($app_dest . '/repository/themes/custom');

				$fs->mkdir($app_dest . '/shared/files');
				$fs->mkdir($app_dest . '/builds');

			} catch (IOExceptionInterface $e) {
				//echo 'An error occurred while creating your directory at '.$e->getPath();
				$io->error(sprintf('An error occurred while creating your directory at ' . $e->getPath()));
			}

			// build repo content
			if (is_dir($utilRoot . '/bundles/d7') && is_dir($app_dest . '/repository')) {
				$d7files = $utilRoot . '/bundles/d7';
				// potential repo files
				$fs->copy($d7files . '/robots.txt', $app_dest . '/repository/robots.txt');
				$fs->copy($d7files . '/settings.php', $app_dest . '/repository/settings.php');
				$fs->copy($d7files . '/project.make.yml', $app_dest . '/repository/project.make.yml');
				$fs->copy($d7files . '/.gitignore', $app_dest . '/repository/.gitignore');
				//local shared files
				$fs->copy($d7files . '/settings.local.php', $app_dest . '/shared/settings.local.php');

				if(isset($reqs) && $reqs == 'Full') {
          $fs->mirror($utilRoot . '/bundles/behat/', $app_dest . '/repository/behat/');
        }
			}

			// deploy an initial build

			//replace this with make.yml script
			$command = 'drush make ' . $app_dest . '/repository/project.make.yml ' . $app_dest . '/builds/' . $date . '/public';

			$io->note('Download and configure Drupal 7.... This may take a few minutes....');
			$this->runcommand($command, $io, TRUE);

			$buildpath = 'builds/' . $date . '/public';
			$fs->symlink($buildpath, $app_dest . '/www', TRUE);

			$rel = $fs->makePathRelative($app_dest . '/repository/', $app_dest . '/' . $buildpath);

			$fs->remove(array($app_dest . '/' . $buildpath . '/robots.txt'));
			$fs->symlink($rel . 'robots.txt', $app_dest . '/' . $buildpath . '/robots.txt', TRUE);

			$fs->remove(array($app_dest . '/' . $buildpath . '/sites/default/settings.php'));
			$fs->symlink('../../' . $rel . 'settings.php', $app_dest . '/' . $buildpath . '/sites/default/settings.php', TRUE);
			$fs->remove(array($app_dest . '/' . $buildpath . '/sites/default/files'));
			$fs->symlink('../../../../../shared/settings.local.php', $app_dest . '/' . $buildpath . '/sites/default/settings.local.php', TRUE);
			$fs->remove(array($app_dest . '/' . $buildpath . '/sites/default/files'));
			$fs->symlink('../../../../../shared/files', $app_dest . '/' . $buildpath . '/sites/default/files', TRUE);

			$fs->symlink($rel . '/sites/default/modules/custom', $app_dest . '/' . $buildpath . '/modules/custom', TRUE);
			$fs->symlink($rel . '/profiles/custom', $app_dest . '/' . $buildpath . '/profiles/custom', TRUE);
			$fs->symlink($rel . '/sites/default/themes/custom', $app_dest . '/' . $buildpath . '/themes/custom', TRUE);

			$fs->chmod($app_dest . '/' . $buildpath . '/sites/default/files', 0777, 0000, TRUE);
			$fs->chmod($app_dest . '/' . $buildpath . '/sites/default/settings.php', 0777, 0000, TRUE);
			$fs->chmod($app_dest . '/' . $buildpath . '/sites/default/settings.local.php', 0777, 0000, TRUE);
		}

	}

	private function setupD8($fs, $io, $appname) {

		$app_dest = './app';
		$application = $this->getApplication();
		$utilRoot = $application->getUtilRoot();

		if(!$fs->exists($app_dest)) {

			$command = sprintf('composer create-project drupal-composer/drupal-project:8.x-dev ' . $app_dest . ' -dir --stability dev --no-interaction');
			$io->note('Download and configure Drupal 8.... This may take a few minutes....');
			$this->runcommand($command, $io, TRUE);

			try {
				$fs->mkdir($app_dest . '/config/sync');
				$fs->mkdir($app_dest . '/web/sites/default/files');
				$fs->mkdir($app_dest . '/web/themes/custom');
				$fs->mkdir($app_dest . '/web/modules/custom');

			} catch (IOExceptionInterface $e) {
				//echo 'An error occurred while creating your directory at '.$e->getPath();
				$io->error(sprintf('An error occurred while creating your directory at ' . $e->getPath()));
			}

			// Move DockerDrupal Drupal 8 config files into install
			if (is_dir($utilRoot . '/bundles/d8') && is_dir($app_dest)) {
				$d8files = $utilRoot . '/bundles/d8';
				$fs->copy($d8files . '/composer.json', $app_dest . '/composer.json', TRUE);
				$fs->copy($d8files . '/development.services.yml', $app_dest . '/web/sites/development.services.yml', TRUE);
				$fs->copy($d8files . '/services.yml', $app_dest . '/web/sites/default/services.yml', TRUE);
				$fs->copy($d8files . '/robots.txt', $app_dest . '/web/robots.txt', TRUE);
				$fs->copy($d8files . '/drushrc.php', $app_dest . '/web/sites/default/drushrc.php', TRUE);
				$fs->copy($d8files . '/settings.php', $app_dest . '/web/sites/default/settings.php', TRUE);
				$fs->copy($d8files . '/settings.local.php', $app_dest . '/web/sites/default/settings.local.php', TRUE);
			}

			// Set perms
			$fs->chmod($app_dest . '/config/sync', 0777, 0000, TRUE);
			$fs->chmod($app_dest . '/web/sites/default/files', 0777, 0000, TRUE);
			$fs->chmod($app_dest . '/web/sites/default/settings.php', 0666, 0000, TRUE);
			$fs->chmod($app_dest . '/web/sites/default/settings.local.php', 0666, 0000, TRUE);

			// Set perms for executables
			$fs->chmod($app_dest . '/vendor', 0777, 0000, TRUE);

			// setup $VAR for redis cache_prefix in settings.local.php template
			$cache_prefix = "\$settings['cache_prefix'] = '" . $appname . "_';";
			$local_settings = $app_dest . '/web/sites/default/settings.local.php';
			$process = new Process(sprintf('echo %s | sudo tee -a %s >/dev/null', $cache_prefix, $local_settings));
			$process->run();

			// Symlink PHP7 container working directory ie. /app/www
			$fs->symlink('./web', $app_dest . '/www', TRUE);
		}
	}

	private function setupExampleApp($fs, $io, $appname) {

    $app_dest = './app';
    $application = $this->getApplication();
    $utilRoot = $application->getUtilRoot();

		$message = 'Setting up Example app';
		$io->note($message);
		// example app source and destination
    if (is_dir($utilRoot . '/bundles/default') && is_dir($app_dest . '/repository')) {
      $app_src = $utilRoot . '/bundles/default';
      try {
        $fs->mkdir($app_dest . '/repository');
        $fs->mirror($app_src, $app_dest . '/repository');
      } catch (IOExceptionInterface $e) {
        echo 'An error occurred while creating your directory at ' . $e->getPath();
      }
      $fs->symlink('repository', './app/www', TRUE);
    }
	}

	private function installDrupal8($io, $install_helpers = FALSE) {

		$message = 'Run Drupal Installation.... This may take a few minutes....';
		$io->note($message);
		$installcmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) chmod -R 777 ../vendor/ && docker exec -i $(docker ps --format {{.Names}} | grep php) drush site-install standard --account-name=dev --account-pass=admin --site-name=DockerDrupal --site-mail=drupalD8@docker.dev --db-url=mysql://dev:DEVPASSWORD@db:3306/dev_db --quiet -y';
		$this->runcommand($installcmd, $io, TRUE);

		if ($install_helpers) {
			$message = 'Run APP composer update';
			$io->note($message);
			$composercmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) composer update';
			$this->runcommand($composercmd, $io, TRUE);
			$message = 'Enable useful starter contrib modules';
			$io->note($message);
			$drushcmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush en admin_toolbar ctools redis token adminimal_admin_toolbar devel pathauto webprofiler -y';
			$this->runcommand($drushcmd, $io, TRUE);
			$drushcmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush entity-updates -y';
			$this->runcommand($drushcmd, $io, TRUE);
		}

	}

	private function installDrupal7($io, $install_helpers = FALSE) {

		$message = 'Run Drupal Installation.... This may take a few minutes....';
		$io->note($message);
		$installcmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush site-install standard --account-name=dev --account-pass=admin --site-name=DockerDrupal --site-mail=drupalD7@docker.dev --db-url=mysql://dev:DEVPASSWORD@db:3306/dev_db -y';

		$this->runcommand($installcmd, $io, TRUE);

//      if($install_helpers) {
//        $message = 'Run APP composer update';
//        $io->note($message);
//        $composercmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) composer update';
//        $this->runcommand($composercmd, $io, TRUE);
//        $message = 'Enable useful starter contrib modules';
//        $io->note($message);
//        $drushcmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush en admin_toolbar ctools redis token adminimal_admin_toolbar devel pathauto webprofiler -y';
//        $this->runcommand($drushcmd, $io, TRUE);
//        $drushcmd = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush entity-updates -y';
//        $this->runcommand($drushcmd, $io, TRUE);
//      }

	}

}
