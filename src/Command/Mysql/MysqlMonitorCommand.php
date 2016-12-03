<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Mysql;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class WatchCommand
 * @package Docker\Drupal\Command\Mysql
 */
class MysqlMonitorCommand extends Command
{
  protected function configure()
  {
      $this
          ->setName('mysql:log')
          ->setDescription('Montitor mysql activity')
          ->setHelp("This command will output MySQL activity.")
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    $io->section("MySQL ::: Monitor");

    if($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if($application->checkForAppContainers($appname, $io)){
      $command = $application->getComposePath($appname, $io).'exec -T db tail -f /var/log/mysql/mysql.log  2>&1';
			$application->runcommand($command, $io);
    }
  }
}