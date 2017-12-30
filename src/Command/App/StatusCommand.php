<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class StatusCommand
 * @package Docker\Drupal\Command
 */
class StatusCommand extends Command {
  protected function configure() {
    $this
      ->setName('app:status')
      ->setAliases(['as'])
      ->setDescription('Get current status of all containers')
      ->setHelp("This command will output a quick status healthcheck of all running containers.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $io = new DruDockStyle($input, $output);
    $io->section("HEALTHCHECK");

    $application->dockerHealthCheck($io);
  }
}