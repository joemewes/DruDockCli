<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Docker\Drupal\Style\DockerDrupalStyle;

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DockerDrupalStyle($input, $output);
        $io->section("STOPPING CONTAINERS");

        $command = 'docker stop $(docker ps -q) 2>&1';
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
           // throw new ProcessFailedException($process);
          if(!exec("echo $(docker ps -q)")){
             $io->warning("No running containers");
          }
        }
        $out = $process->getOutput();
        $io->info($out);
    }
}