<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class DrushClearCacheCommand extends Command {
	protected function configure() {
		$this
			->setName('drush:cc')
			->setDescription('Run drush cache clear ')
			->setHelp("This command will clear Drupal APP caches.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$application = $this->getApplication();

		$io = new DockerDrupalStyle($input, $output);

		$config = $application->getAppConfig($io);

		if ($config) {
			$type = $config['apptype'];
		}

		if (isset($type) && $type == 'D8') {
			$cmd = 'cr all';
		} elseif (isset($type) && $type == 'D7') {
      $cmd = 'cc all';
    }	else {
      $io->error('You\'re not currently in an Drupal APP directory');
      return;
		};

		$io->section('EXEC drush ' . $cmd);
		$command = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush ' . $cmd;
		$application->runcommand($command, $io);

	}
}