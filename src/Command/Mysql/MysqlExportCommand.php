<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Mysql;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class MysqlImportExportCommand
 * @package Docker\Drupal\Command\Mysql
 */
class MysqlExportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('mysql:export')
            ->setDescription('Export .sql files')
            ->setHelp("Use this to dump .sql files to the current running APPs dev_db. eg. [dockerdrupal mysql:export -p ./latest.sql]")
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Specify export file path including filename [./latest.sql]')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();

        $io = new DockerDrupalStyle($input, $output);
        $io->section('EXEC '.$cmd);

        $config = $application->getAppConfig($io);
        if($config) {
            $appname = $config['appname'];
            $type = $config['apptype'];
        }

        // GET AND SET APP TYPE
        $path = $input->getOption('type');

        if(!$path){
            //specify save path
            $helper = $this->getHelper('question');
            $question = new Question('Specify save path, including filename [latest.sql] : ', 'latest-'.$appname.'.sql');
            $savepath = $helper->ask($input, $output, $question);
            $command = 'docker exec -i $(docker ps --format {{.Names}} | grep db) mysqldump -u dev -pDEVPASSWORD dev_db > '.$savepath;
						$application->runcommand($command, $io);
        }
    }
}