<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\NginxProxyStopCommand.
 */

namespace Docker\Drupal\Command\Nginx;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Yaml\Yaml;

/**
 * Class NginxProxyStopCommand
 *
 * @package Docker\Drupal\Command\Nginx
 */
class NginxProxyStopCommand extends Command
{

    const APPNAME = 'appname';

    const DIST = 'dist';

    const HOST = 'host';

    protected function configure()
    {
        $this
        ->setName('nginx:proxy:stop')
        ->setDescription('Stop nginx proxy')
        ->setHelp("Stop DruDock nginx-proxy for all running apps.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $container_application = new ApplicationContainerExtension();
        $io = new DruDockStyle($input, $output);

        $io->section("Proxy ::: Stop");

        if ($config = $application->getAppConfig($io)) {
            $appname = $config[self::APPNAME];
            $apptype = $config[self::DIST];
        }

        if ($container_application->checkForAppContainers($appname, $io)) {
          // Check if depends healthchecks are required.
            if (isset($apptype) && $apptype !== 'Development') {
                $io->warning("Nginx proxy is for local development purposes and should be used with Development apps.");
            }

            $command = 'docker ps -f name=drudock-proxy | grep drudock-proxy';
            if (shell_exec($command)) {
                $io->info("Stopping proxy container.");
                $command = 'docker stop drudock-proxy';
                $application->runcommand($command, $io);
            }
        }
    }
}
