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
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class WatchCommand
 * @package Docker\Drupal\Command\redis
 */
class RedisFlushCommand extends Command {

  protected function configure() {
    $this
      ->setName('redis:flush')
      ->setDescription('Flush Redis cache')
      ->setHelp("This command will flush all data REDIS key/value store (cache).");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $container_application = new ApplicationContainerExtension();
    $io = new DruDockStyle($input, $output);

    $io->section("REDIS ::: flushall");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $application->getComposePath($appname, $io) . ' exec -T redis redis-cli flushall';
      $application->runcommand($command, $io);
    }
  }

}
