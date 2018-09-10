<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\BashCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class BashCommand
 *
 * @package Docker\Drupal\Command
 */
class BashCommand extends Command
{

    protected function configure()
    {
        $this
        ->setName('app:bash')
        ->setAliases(['ab'])
        ->setDescription('Bash into container')
        ->addArgument('service', InputArgument::REQUIRED, 'Specify NAME of service')
        ->setHelp("Example : [drudock app:bash mysql]");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $container_application = new ApplicationContainerExtension();

        $io = new DruDockStyle($input, $output);

        if ($config = $application->getAppConfig($io)) {
            $appname = $config['appname'];
        } else {
            $appname = 'app';
        }

        $serviceName = $input->getArgument('service');

        $io->section("APP ::: bash " . $serviceName);

        if ($container_application->checkForAppContainers($appname, $io)) {
            $command = $container_application->getComposePath($appname, $io) . ' exec ' . $serviceName . ' bash';
            $application->runcommand($command, $io, true, 0, 300);
        }
    }
}
