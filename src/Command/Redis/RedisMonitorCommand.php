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
class RedisMonitorCommand extends Command
{
  protected function configure()
  {
      $this
          ->setName('redis:monitor')
          ->setDescription('Montitor redis activity')
          ->setHelp("This command will output REDIS activity.")
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    $io->section("REDIS ::: Monitor");

    if($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if($application->checkForAppContainers($appname, $io)){
      $command = $application->getComposePath($appname, $io).'exec -T redis redis-cli monitor  2>&1';
    }

    $process = new Process($command);
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }
    $out = $process->getOutput();
    $io->info($out);
  }
}