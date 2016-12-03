<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class DrushModuleEnableCommand extends Command {
	protected function configure() {
		$this
			->setName('drush:en')
			->setDescription('Enable Drupal module')
			->setHelp("This command will enable Drupal module. [dockerdrupal drush:en myModule]")
			->addArgument('modulename', InputArgument::OPTIONAL, 'Specify NAME of module');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$application = $this->getApplication();

		$io = new DockerDrupalStyle($input, $output);

		// GET AND SET APPNAME
		$modulename = $input->getArgument('modulename');
		if(!$modulename){
			$io->title("SET MODULE NAME");
			$helper = $this->getHelper('question');
			$question = new Question('Enter module name : ');
			$modulename = $helper->ask($input, $output, $question);
		}

		$config = $application->getAppConfig($io);

		if ($config) {
			$type = $config['apptype'];
		}

		if ($type == 'D8') {
			$cmd = 'pm-enable ' . $modulename . ' -y';
		}
		else {
			if ($type == 'D7') {
				$cmd = 'en ' . $modulename . ' -y';
			}
			else {
				$io->error('You\'re not currently in an APP directory');
				return;
			}
		}

		$io->section('EXEC drush ' . $cmd);
		$command = 'docker exec -i $(docker ps --format {{.Names}} | grep php) drush ' . $cmd;
		$application->runcommand($command, $io);

	}
}