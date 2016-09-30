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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DockerDrupalStyle;
use Symfony\Component\Yaml\Yaml;


/**
* Class DemoCommand
* @package Docker\Drupal\ContainerAwareCommand
*/
class InitCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
        ->setName('env:init')
        ->setAliases(['env'])
        ->setDescription('Fetch and build DockerDrupal containers')
        ->setHelp('This command will fetch the specified DockerDrupal config, download and build all necessary images.  NB: The first time you run this command it will need to download 4GB+ images from DockerHUB so make take some time.  Subsequent runs will be much quicker.')
        ->addArgument('appname', InputArgument::OPTIONAL, 'Specify NAME of application to build [app-dd-mm-YYYY]')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);

    // check Docker is running
    $application->checkDocker($io, TRUE);

    $fs = new Filesystem();
    $date =  date('Y-m-d--H-i-s');

    //$io->section("ADD HOSTS");
    $message = 'If required, please type admin password to add \'127.0.0.1 docker.dev\' to \/etc\/hosts';
    $io->note($message);
    $this->addHost();

    $appname = $input->getArgument('appname');

    if(!$appname){
        $io->title("SET APP NAME");
        $helper = $this->getHelper('question');
        $question = new Question('Enter App name [dd_app_'.$date.'] : ', 'my-app-'.$date);
        $appname = $helper->ask($input, $output, $question);
    }

    $system_appname = strtolower(str_replace(' ', '', $appname));

    // check if this folder is has APP config
    if(file_exists('.config.yml')){
        $io->error('You\'re currently in an APP directory');
        return;
    }

    if(!$fs->exists($system_appname)){
        $fs->mkdir($system_appname , 0755);
        $fs->mkdir($system_appname.'/docker_'.$system_appname, 0755);
    }else{
        $io->error('This app already exists');
        return;
    }

    // SETUP APP CONFIG FILE
    $config= array(
        'Appname' => $appname,
        'DockerDrupal' => array('version' => $application->getVersion(), 'date' => $date),
    );
    $yaml = Yaml::dump($config);
    file_put_contents($system_appname.'/.config.yml', $yaml);

    $message = 'Fetching DockerDrupal v'.$application->getVersion();
    $io->info(' ');
    $io->note($message);
    $command = 'git clone https://github.com/4alldigital/DockerDrupal-lite.git '.$system_appname.'/docker_'.$system_appname;
    $this->runcommand($command, $io, TRUE);

    $this->initDocker($io, $system_appname);

    $message = 'DockerDrupal containers ready. Navigate to your app folder and build your app via ::: build:init';
    $io->info(' ');
    $io->note($message);

  }

  protected function runcommand($command, $io, $showoutput = TRUE){

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
  }

  private function initDocker($io, $appname){

    if(exec('docker ps -q 2>&1', $exec_output)) {
        $dockerstopcmd = 'docker stop $(docker ps -q)';
        $this->runcommand($dockerstopcmd, $io, TRUE);
    }

    $message = 'Download and configure DockerDrupal.... This may take a few minutes....';
    $io->note($message);

    $dockerlogs = 'docker-compose -f '.$appname.'/docker_'.$appname.'/docker-compose.yml logs -f';
    $this->runcommand($dockerlogs, $io, TRUE);

    if(exec('docker ps -q 2>&1', $exec_output)) {
      $dockercmd = 'docker kill $(docker ps -q)';
      $this->runcommand($dockercmd, $io, TRUE);
    }

    $dockercmd = 'docker-compose -f '.$appname.'/docker_'.$appname.'/docker-compose.yml pull';
    $this->runcommand($dockercmd, $io, TRUE);
  }

  private function addHost(){
    // add initial entry to hosts file -> OSX @TODO update as command for all systems and OS's
    $ip = '127.0.0.1';
    $hostname = 'docker.dev';
    $hosts_file = '/etc/hosts';
    if(!exec("cat ".$hosts_file." | grep '".$ip." ".$hostname."'")) {
      $process = new Process(sprintf('echo "%s %s" | sudo tee -a %s >/dev/null', $ip, $hostname, $hosts_file));
      $process->run(
        function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        }
      );
    }
  }

}