<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class InitCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('docker:init')
            ->setAliases(['init'])
            ->setDescription('Fetch and build DockerDrupal containers')
            ->setHelp("This command will fetch the specified DockerDrupal config, download and build all necessary images.")
            ->addArgument('app', InputArgument::REQUIRED, 'Specify application(s) to build')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, '', getcwd())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DockerDrupalStyle($input, $output);
        $appname = $input->getArgument('app');
        $fs = new Filesystem();

        if(!$fs->exists($appname)){
            $fs->mkdir($appname , 0755);
            $fs->mkdir($appname.'/docker_'.$appname , 0755);
        }

        $message = 'Downloading DockerDrupal v.1.0';
        $io->note($message);
        exec('git clone https://github.com/4alldigital/DockerDrupal-lite.git --quiet '.$appname.'/docker_'.$appname);

        $message = 'Setting up Example app';
        $io->note($message);
        // example app source and destination
        $app_src = $appname.'/docker_'.$appname.'/example/app/';
        $app_dest = $appname.'/app/repository/';

        try {
            $fs->mkdir($app_dest);
            $fs->mirror($app_src, $app_dest);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your directory at ".$e->getPath();
        }

        $io->note($appname.'www');
        $fs->symlink('repository', $appname.'/app/www', true);

        // docker-compose -f myapp21/docker_myapp21/docker-compose.yml up -d
        $message = 'Stopping and running containers';
        $io->note($message);
        system('docker stop $(docker ps -q)');

        $message = 'Creating app network, volumes and containers.';
        $io->note($message);
        system('docker-compose -f '.$appname.'/docker_'.$appname.'/docker-compose.yml up -d');

    }

}