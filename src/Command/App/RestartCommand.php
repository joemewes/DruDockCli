<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\RestartCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class RestartCommand
 *
 * @package Docker\Drupal\Command
 */
class RestartCommand extends Command
{

    protected function configure()
    {
        $this
        ->setName('app:restart')
        ->setAliases(['ar'])
        ->setDescription('Restart current APP containers')
        ->setHelp("This command will restart all containers for the current APP via the docker-compose.yml file.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $container_application = new ApplicationContainerExtension();

        $io = new DruDockStyle($input, $output);
        $io->section("RESTARTING CONTAINERS");


        if ($config = $application->getAppConfig($io)) {
            $appname = $config['appname'];
        }

        if ($container_application->checkForAppContainers($appname, $io)) {
            $command = $container_application->getComposePath($appname, $io) . 'restart 2>&1';
            $application->runcommand($command, $io);
        }
    }
}
