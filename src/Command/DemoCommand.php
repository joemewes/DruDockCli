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
class DemoCommand extends Command
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
           //->setName('demo:greet')
            ->setName('echo')

            // the short description shown wh ile running "php bin/console list"
            ->setDescription('Demo Command')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command allows you to create users...")

            ->addArgument(
                'value',
                InputArgument::OPTIONAL,
                'A String you want to echo',
                'crick crick'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $value = $input->getArgument('value');
        $fs = new Filesystem();
        $fs->mkdir('/tmp/photos', 0700);
        $output->writeln($value);
    }
}