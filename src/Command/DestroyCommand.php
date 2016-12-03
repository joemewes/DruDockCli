<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Docker\Drupal\Style\DockerDrupalStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class DestroyCommand extends Command {
	protected function configure() {
		$this
			->setName('build:destroy')
			->setAliases(['destroy'])
			->setDescription('Disable and delete APP and containers')
			->setHelp("This command will completely remove all containers and volumes for the current APP via the docker-compose.yml file.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$application = $this->getApplication();

		$io = new DockerDrupalStyle($input, $output);
		$io->section("REMOVING APP");

		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion('Are you sure you want to delete this app? [y/n] : ', FALSE);
		if (!$helper->ask($input, $output, $question)) {
			return;
		}

		$config = $application->getAppConfig($io);
		if ($config) {
			$appname = $config['appname'];
			$type = $config['apptype'];
		}

		$fs = new Filesystem();
		if ($fs->exists('docker-compose.yml')) {
			$command = 'docker-compose down -v';
		}
		elseif ($fs->exists('./docker_' . $appname . '/docker-compose.yml')) {
			$command = 'docker-compose -f ./docker_' . $appname . '/docker-compose.yml down -v';
		}
		else {
			$io->warning("docker-compose.yml : Not Found");
			return;
		}

		$application->runcommand($command, $io);
	}
}