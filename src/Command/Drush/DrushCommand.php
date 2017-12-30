<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class DrushCommand
 *
 * @package Docker\Drupal\Command
 */
class DrushCommand extends Command {

  protected function configure() {
    $this
      ->setName('drush:cmd')
      ->setAliases(['dc'])
      ->setDescription('Run drush commands ')
      ->setHelp("This command will execute Drush commands directly against your Drupal APP.")
      ->addOption('cmd', 'c', InputOption::VALUE_OPTIONAL, 'Specify the command ["bash"]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $cmd = $input->getOption('cmd');

    $io = new DruDockStyle($input, $output);
    $io->section('PHP ::: drush ' . $cmd);

    if (!$cmd) {
      $helper = $this->getHelper('question');
      $question = new Question('Enter command : ', 'bash');
      $cmd = $helper->ask($input, $output, $question);
    }

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }
    else {
      $appname = 'app';
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . ' exec -T php drush ' . $cmd;
      $application->runcommand($command, $io);
    }
  }

}

