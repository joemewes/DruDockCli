<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
			$appname = $config['appname'];
			$type = $config['apptype'];
		}

		if ($type == 'D8') {
			$cmd = 'cr all';
		}
		else {
			if ($type == 'D7') {
				$cmd = 'cc all';
			}
			else {
				$io->error('You\'re not currently in an APP directory');
				return;
			}
		}

		$io->section('EXEC drush ' . $cmd);
		$command = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush ' . $cmd;

		$process = new Process($command);
		$process->setTimeout(60);
		$process->run();

		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
		$out = $process->getOutput();
		$io->info($out);

	}
}