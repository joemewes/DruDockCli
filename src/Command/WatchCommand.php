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

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
//use Symfony\Component\Console\Question\Question;
//use Symfony\Component\Process\Process;
//use Symfony\Component\Process\Exception\ProcessFailedException;
//use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class WatchCommand
 * @package Docker\Drupal\Command
 */
class WatchCommand extends Command
{
  protected function configure()
  {
      $this
          ->setName('docker:logs')
          ->setAliases(['logs'])
          ->setDescription('Montitor logs output of container')
          ->setHelp("This command will output logs for this container.")
          ->addOption('service', 's', InputOption::VALUE_OPTIONAL, 'Specify the service/container [php]')
      ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    $service = $input->getOption('service');

    $running_containers = $application->getRunningContainerNames();
    foreach($running_containers as $c){
      $name_parts = explode('_', $c);
      $available_services[] = $name_parts[1];
    }

    if(!$service){
      $helper = $this->getHelper('question');
      $question = new ChoiceQuestion(
        'Which service/container? : ',
        $available_services
      );
      $service = $helper->ask($input, $output, $question);
    }

    $io->section("WATCHING CONTAINER ::: " . $service);

//    $config = $application->getAppConfig($io);
//
//    if($config) {
//      $appname = $config['appname'];
//      $type = $config['apptype'];
//    }
//
//    $command = 'docker logs -f $(docker ps --format {{.Names}} | grep '.$service.') 2>&1';
//    $command = 'docker-compose -f ./docker_'.$appname.'/docker-compose.yml run app 2>&1 | grep -m 1 -e "Synchronization complete" -e "Nothing to do"; do : ; done';

    if($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

//    if($application->checkForAppContainers($appname, $io)){
//      switch ($service) {
//        case 'redis':
//          $command = $application->getComposePath($appname).'exec '.$service.' redis-cli monitor';
//          break;
//        case 'nginx':
//          $command = $application->getComposePath($appname).'exec '.$service.' tail -f /var/log/nginx/app-error.log';
//          break;
//        default :
//          $command = $application->getComposePath($appname).'logs -f '.$service;
//      }
//    }

    if($application->checkForAppContainers($appname, $io)){
      $command = $application->getComposePath($appname).'logs '.$service;
    }

    $process = new Process($command);
    $process->setTimeout(60);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }
    $out = $process->getOutput();
    $io->info($out);
  }
}