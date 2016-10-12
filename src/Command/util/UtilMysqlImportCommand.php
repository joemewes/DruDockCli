<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Util;

use Symfony\Component\Console\Command\Command;
//use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class UtilMysqlImportExportCommand
 * @package Docker\Drupal\Command\util
 */
class UtilMysqlImportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('util:mysql:import')
            ->setAliases(['utilmi'])
            ->setDescription('Import .sql files [utilmi]')
            ->setHelp("Use this to import .sql files to the current running APPs dev_db. eg. [dockerdrupal util:mysql:import -p ./latest.sql]")
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Specify import file path including filename')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();

        $io = new DockerDrupalStyle($input, $output);

        $config = $application->getAppConfig($io);
        if($config) {
            $appname = $config['appname'];
            $type = $config['apptype'];
        }

        // GET AND SET APP TYPE
        $path = $input->getOption('path');
        if(!$path){
            // specify import path
            $helper = $this->getHelper('question');
            $question = new Question('Specify import path, including filename : ');
            $importpath = $helper->ask($input, $output, $question);
            if(file_exists($importpath)){
                $command = 'docker exec -i $(docker ps --format {{.Names}} | grep db) mysql -u dev -pDEVPASSWORD dev_db < '.$importpath;
            }else{
                $io->error('Import .sql file not found at path '.$importpath);
                exit;
            }
        }

        global $output;
        $output = $io;

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) {
            global $output;
            if($output) {
                $output->info($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        if ($process->isSuccessful()) {
            $io->success('MySQL '.$type.' complete.');
        }

    }
}