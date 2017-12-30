<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;

/**
 * Class DrushModuleDisableCommand
 * @package Docker\Drupal\Command
 */
class DrushModuleDisableCommand extends Command {

  protected function configure() {
    $this
      ->setName('drush:dis')
      ->setAliases('dd')
      ->setDescription('Disable/Uninstall Drupal module')
      ->setHelp("This command will disable (D7) or uninstall (D8) Drupal  contrib modules. [drudock drush:dis myModule]")
      ->addArgument('modulename', InputArgument::OPTIONAL, 'Specify NAME of module');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);

    $modulename = $input->getArgument('modulename');
    if (!$modulename) {
      $io->title("SET MODULE NAME");
      $helper = $this->getHelper('question');
      $question = new Question('Enter module name : ');
      $modulename = $helper->ask($input, $output, $question);
    }

    $config = $application->getAppConfig($io);

    if ($config) {
      $type = $config['apptype'];
    }

    if ($type == 'D8') {
      $cmd = 'pm-uninstall ' . $modulename . ' -y';
    }
    else {
      if ($type == 'D7') {
        $cmd = 'dis ' . $modulename . ' -y';
      }
      else {
        $io->error('You\'re not currently in an APP directory');
        return;
      }
    }

    $io->section('PHP ::: drush ' . $cmd);
    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {
      $command = $container_application->getComposePath($appname, $io) . ' exec -T php drush ' . $cmd;
      $application->runcommand($command, $io);
    }
  }

}
