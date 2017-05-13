<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Behat;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class BehatCommand
 *
 * @package Docker\Drupal\Command
 */
class BehatCommand extends Command {

  const QUESTION = 'question';

  protected function configure() {
    $this
      ->setName('behat:cmd')
      ->setDescription('Run behat commands')
      ->setHelp("Example : [drudock behat:cmd --suite=global_features --profile=local --tags=about]")
      ->addOption('suite', '-s', InputOption::VALUE_OPTIONAL, 'Suite of features to test [global_features]')
      ->addOption('profile', '-p', InputOption::VALUE_OPTIONAL, 'Profile to test [local]')
      ->addOption('tags', '-t', InputOption::VALUE_OPTIONAL, 'Tags to test [about]');
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }
    else {
      $appname = 'app';
    }

    if (!$suite = $input->getOption('suite')) {
      $helper = $this->getHelper(QUESTION);
      $question = new Question('Suite [global_features] : ', 'global_features');
      $suite = $helper->ask($input, $output, $question);
    }

    if (!$profile = $input->getOption('profile')) {
      $helper = $this->getHelper(QUESTION);
      $question = new Question('Profile [local] : ', 'local');
      $profile = $helper->ask($input, $output, $question);
    }

    if (!$tags = $input->getOption('tags')) {
      $helper = $this->getHelper(QUESTION);
      $question = new Question('Profile [about] : ', 'about');
      $tags = $helper->ask($input, $output, $question);
    }

    if ($container_application->checkForAppContainers($appname, $io)) {

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

    $io->section("BEHAT ::: " . $cmd);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . 'exec behat behat ' . $cmd;
      $application->runcommand($command, $io);
    }
  }
}
