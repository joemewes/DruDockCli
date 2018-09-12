<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\BehatStatusCommand.
 */

namespace Docker\Drupal\Command\Behat;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class BehatStatusCommand
 *
 * @package Docker\Drupal\Command
 */
class BehatStatusCommand extends Command
{

    protected function configure()
    {
        $this
        ->setName('behat:status')
        ->setDescription('Runs example command against running APP and current config')
        ->setHelp("Currently hardcoded options [behat:status]");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $container_application = new ApplicationContainerExtension();
        $io = new DruDockStyle($input, $output);

        $cmd = '--config /root/behat/behat.yml --suite global_features --profile local --tags about';
        $io->section("BEHAT ::: Example :: " . $cmd);

        if ($config = $application->getAppConfig($io)) {
            $appname = $config['appname'];
        } else {
            $appname = 'app';
        }

        if ($container_application->checkForAppContainers($appname, $io)) {
            $command = $container_application->getComposePath($appname, $io) . 'exec behat behat ' . $cmd;
            $application->runcommand($command, $io);
        }
    }
}
