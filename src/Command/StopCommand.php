<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class StopCommand extends Command
{
    protected function configure()
    {
        $this
//            ->setName('local:build')
//            ->setAliases(['build'])
//            ->addArgument('app', InputArgument::IS_ARRAY, 'Specify application(s) to build')
//            ->setDescription('Build the current project locally')
//            ->addOption(
//                'abslinks',
//                'a',
//                InputOption::VALUE_NONE,
//                'Use absolute links'
//            )
            // the name of the command (the part after "bin/console")
           //->setName('demo:greet')
            ->setName('stop')

            // the short description shown wh ile running "php bin/console list"
            ->setDescription('Stop all containers')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command will stop all running containers even if you're in another app/project folder...")

//            ->addArgument(
//                'value',
//                InputArgument::OPTIONAL,
//                'A String you want to echo',
//                'crick crick'
//            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        system('docker images', $ret);
//        docker stop $(docker ps -q)
//        $value = $input->getArgument('value');
//        $fs = new Filesystem();
//        $fs->mkdir('/tmp/photos', 0700);
//        $output->writeln($value);
    }
}