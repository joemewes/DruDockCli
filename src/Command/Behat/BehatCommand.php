<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Behat;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Docker\Drupal\Style\DockerDrupalStyle;

/**
 * Class BehatCommand
 * @package Docker\Drupal\Command
 */
class BehatCommand extends Command {

  protected function configure() {
    $this
      ->setName('behat:cmd')
      ->setDescription('Run behat commands')
      ->setHelp("Example : [dockerdrupal behat:cmd --suite=global_features --profile=local --tags=about]")
      ->addOption('suite', '-s', InputOption::VALUE_OPTIONAL, 'Suite of features to test [global_features]')
      ->addOption('profile', '-p', InputOption::VALUE_OPTIONAL, 'Profile to test [local]')
      ->addOption('tags', '-t', InputOption::VALUE_OPTIONAL, 'Tags to test [about]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();

    $io = new DockerDrupalStyle($input, $output);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if (!$suite = $input->getOption('suite')) {
      $helper = $this->getHelper('question');
      $question = new Question('Suite [global_features] : ', 'global_features');
      $suite = $helper->ask($input, $output, $question);
    }

    if (!$profile = $input->getOption('profile')) {
      $helper = $this->getHelper('question');
      $question = new Question('Profile [local] : ', 'local');
      $profile = $helper->ask($input, $output, $question);
    }

    if (!$tags = $input->getOption('tags')) {
      $helper = $this->getHelper('question');
      $question = new Question('Profile [about] : ', 'about');
      $tags = $helper->ask($input, $output, $question);
    }

    if ($application->checkForAppContainers($appname, $io)) {

      $cmd = '--config /root/behat/behat.yml ';
      if (isset($suite) && $suite != NULL) {
        $cmd .= ' --suite ' . $suite;
      }

      if (isset($profile) && $profile != NULL) {
        $cmd .= ' --profile ' . $profile;
      }

      if (isset($tags) && $tags != NULL) {
        $cmd .= ' --tags ' . $tags;
      }
    }

    $io->section('EXEC behat ' . $cmd);
    $command = 'docker exec -it $(docker ps --format {{.Names}} | grep behat) sh -c "behat ' . $cmd . '"';
    $application->runcommand($command, $io);

  }

}
