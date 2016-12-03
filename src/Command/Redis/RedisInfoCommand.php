<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Redis;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class WatchCommand
 * @package Docker\Drupal\Command\redis
 */
class RedisInfoCommand extends Command
{
  protected function configure()
  {
      $this
          ->setName('redis:info')
          ->setDescription('Get Redis running config information')
          ->setHelp("This command will output current running REDIS instance information/config.")
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    $io->section("REDIS ::: Info");

    if($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if($application->checkForAppContainers($appname, $io)){
      $command = $application->getComposePath($appname, $io).'exec -T redis redis-cli info';
			$application->runcommand($command, $io);
    }
  }
}