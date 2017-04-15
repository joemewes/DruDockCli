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
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class DemoCommand
 * @package Docker\Drupal\Command
 */
class StatusCommand extends Command {
  protected function configure() {
    $this
      ->setName('docker:status')
      ->setAliases(['status'])
      ->setDescription('Get current status of all containers')
      ->setHelp("This command will output a quick status healthcheck of all running containers.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $io = new DruDockStyle($input, $output);
    $io->section("HEALTHCHECK");

    $application->dockerHealthCheck($io);
  }
}