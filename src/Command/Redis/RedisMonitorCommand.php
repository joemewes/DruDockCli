<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\RedisMonitorCommand.
 */

namespace Docker\Drupal\Command\Redis;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class RedisMonitorCommand
 *
 * @package Docker\Drupal\Command\redis
 */
class RedisMonitorCommand extends Command
{

    protected function configure()
    {
        $this
        ->setName('redis:monitor')
        ->setDescription('Monitor redis activity')
        ->setHelp("This command will output REDIS activity.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $container_application = new ApplicationContainerExtension();
        $io = new DruDockStyle($input, $output);

        $io->section('REDIS ::: Monitor');

        if ($config = $application->getAppConfig($io)) {
            $appname = $config['appname'];
        }

        if ($container_application->checkForAppContainers($appname, $io)) {
            $command = $container_application->getComposePath($appname, $io) . 'exec -T redis redis-cli monitor  2>&1';
            $application->runcommand($command, $io);
        }
    }
}
