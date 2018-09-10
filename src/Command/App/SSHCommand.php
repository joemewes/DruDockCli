<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\SSHCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class SSHCommand
 *
 * @package Docker\Drupal\Command
 */
class SSHCommand extends Command
{

    protected $app;

    protected $cfa;

    protected $cta;

    protected function configure()
    {
        $this
        ->setName('app:ssh')
        ->setAliases(['assh'])
        ->setDescription('SSH into Apps PHP container.')
        ->setHelp("Example : [drudock app:ssh]")
        ->addOption('key', 'k', InputOption::VALUE_OPTIONAL, 'Specify path to SSH key that has already been added to project authorized_keys file. ["./config/php/drudock"]');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $container_application = new ApplicationContainerExtension();

        $io = new DruDockStyle($input, $output);

        if ($config = $application->getAppConfig($io)) {
            $appname = $config['appname'];
            $hosts = explode(' ', $config['host']);
            $host = $hosts[0];
        } else {
            $appname = 'app';
            $host = 'drudock.localhost';
        }

        $key = $input->getOption('key');

        $io->section('PHP ::: drush ' . $cmd);

        if ($key) {
            $keypath = $key;
        } else {
            $keypath = './docker_' . $system_appname . '/config/php/drudock';
        }

        $io->section("APP ::: SSH " . $appname);

        if ($container_application->checkForAppContainers($appname, $io)) {
            $this->cfa = new ApplicationConfigExtension();
            $system_appname = strtolower(str_replace(' ', '', $appname));
            $ssh_port = $this->cfa->containerPort($system_appname, 'php', '22');
            if (!file_exists('./docker_' . $system_appname . '/config/php/drudock')) {
                $io->error('SSH key missing at `./docker_' . $system_appname . '/config/php/drudock` ');
            }
            $command = 'ssh -i '. $keypath . ' -tt root@' . $host . ' -p ' . $ssh_port . ' "cd /app/www ; bash"';
            $application->runcommand($command, $io, true);
        }
    }
}
