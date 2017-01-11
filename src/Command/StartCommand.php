<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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

    $io->section("APP ::: Starting " . $appname . " containers");

   // $command = '$(docker ps | grep docker | wc -l)';
    if(exec("docker ps | grep docker | wc -l") > 0){

      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion('You have other containers running. Would you like to stop them? ', false);

      if (!$helper->ask($input, $output, $question)) {
        return;
      }

      $io->info(' ');
      $command = "docker stop $(docker ps -q)";
      $application->runcommand($command, $io);

      if($application->checkForAppContainers($appname, $io)){
        $command = $application->getComposePath($appname, $io).' start 2>&1';
        $application->runcommand($command, $io);
      }

    } else {
      if($application->checkForAppContainers($appname, $io)){
        $command = $application->getComposePath($appname, $io).' start 2>&1';
        $application->runcommand($command, $io);
      }
    }
  }
}