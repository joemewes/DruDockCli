<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\UpdateCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class UpdateCommand
 * @package Docker\Drupal\Command
 */
class UpdateCommand extends Command
{

    protected function configure()
    {
        $this
        ->setName('app:update:containers')
        ->setAliases(['auct'])
        ->setDescription('Update APP containers')
        ->setHelp("This command will update all containers from https://hub.docker.com for the current APP via the docker-compose.yml file. [drudock auct --force=true --pull=true]")
        ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Optional argument to force update all containers.')
        ->addOption('pull', 'p', InputOption::VALUE_OPTIONAL, 'Optional argument to pull all containers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $container_application = new ApplicationContainerExtension();
        $io = new DruDockStyle($input, $output);
        $io->section("UPDATING CONTAINERS");

        if ($config = $application->getAppConfig($io)) {
            $appname = $config['appname'];

            if ($container_application->checkForAppContainers($appname, $io)) {
                $pull = (BOOL) $input->getOption('pull');
                if ($pull) {
                    $command = $container_application->getComposePath($appname, $io) . ' pull 2>&1';
                    $application->runcommand($command, $io);
                }
                // check args.
                $force = (BOOL) $input->getOption('force');
                if ($force) {
                    $command = $container_application->getComposePath($appname, $io) . ' up -d --force-recreate 2>&1';
                } else {
                    $command = $container_application->getComposePath($appname, $io) . ' up -d 2>&1';
                }
                $application->runcommand($command, $io);
            }
        }
    }
}
