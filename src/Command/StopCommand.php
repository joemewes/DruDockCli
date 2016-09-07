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

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class StopCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('docker:stop')
            ->setAliases(['stop'])
            ->setDescription('Stop all containers')
            ->setHelp("This command will stop all running containers even if you're in another app/project folder...")

            //->addArgument('app', InputArgument::IS_ARRAY, 'Specify application(s) to build')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, '', getcwd())
//            ->addOption(
//                'abslinks',
//                'a',
//                InputOption::VALUE_NONE,
//                'Use absolute links'
//            )
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
        //system('docker images', $ret);

        $path = $input->getOption('path').'/LICENSE';
        $license = implode(' ', array(
            'Copyright (c)',
        ));

        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, $license.PHP_EOL);

        $output->writeln(sprintf('Created the file %s', $path));

//        $value = $input->getArgument('value');
//        $fs = new Filesystem();
//        $fs->mkdir('/tmp/photos', 0700);
//        $output->writeln($value);
    }
}