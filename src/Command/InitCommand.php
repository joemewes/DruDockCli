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
//use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Docker\Drupal\Style\DockerDrupalStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

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
        $io = new DockerDrupalStyle($input, $output);

        $appname = $input->getArgument('app');
        $fs = new Filesystem();
        $fs->mkdir($appname , 0755);
        $fs->mkdir($appname.'/docker_'.$appname , 0755);

       // $gitcmd = 'git clone https://github.com/4alldigital/DockerDrupal-lite.git '.$appname.'/docker_'.$appname.' --quiet';
        //$output->writeln('<info>Downloading DockerDrupal v.1.0</info>');
//        $style = new OutputFormatterStyle('black', 'blue', array('bold'));
//        $output->getFormatter()->setStyle('dd', $style);
        $message = 'Downloading DockerDrupal v.1.0';
        $io->note($message);


//
//        $output->writeln('<dd><br />Downloading DockerDrupal v.1.0<br /></dd>');

        exec('git clone https://github.com/4alldigital/DockerDrupal-lite.git --quiet '.$appname.'/docker_'.$appname);
        //$output->writeln(sprintf('Type to download is %s', $appname ));
    }
}