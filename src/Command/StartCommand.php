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
class StartCommand extends Command {
	protected function configure() {
		$this
			->setName('docker:start')
			->setAliases(['start'])
			->setDescription('Start APP containers')
			->setHelp("Example : [dockerdrupal start]");
	}

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    if($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    $io->section("Starting APP:' . $appname . ' Containers");

    if($application->checkForAppContainers($appname, $io)){
      $command = $application->getComposePath($appname, $io).' start 2>&1';
      $application->runcommand($command, $io);
    }
  }


}