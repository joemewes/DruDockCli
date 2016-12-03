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

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class UpdateCommand extends Command {
	protected function configure() {
		$this
			->setName('docker:update')
			->setAliases(['update'])
			->setDescription('Update APP containers')
			->setHelp("This command will update all containers from https://hub.docker.com for the current APP via the docker-compose.yml file.");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$application = $this->getApplication();
		$io = new DockerDrupalStyle($input, $output);
		$io->section("UPDATING CONTAINERS");

		if($config = $application->getAppConfig($io)) {
			$appname = $config['appname'];
		}

		if($application->checkForAppContainers($appname, $io)){

			// update images from docker hub
			$command = $application->getComposePath($appname, $io).' pull 2>&1';
			$application->runcommand($command, $io);

			// recreate containers
			$command = $application->getComposePath($appname, $io).' up -d --force-recreate 2>&1';
			$application->runcommand($command, $io);

		}
	}

}