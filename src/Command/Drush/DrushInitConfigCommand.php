<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Yaml\Yaml;


/**
 * Class DrushInitConfigCommand
 * @package Docker\Drupal\Command
 */
class DrushInitConfigCommand extends Command {

  protected function configure() {
    $this
      ->setName('drush:init:config')
      ->setDescription('Run drush config init')
      ->setHelp("This command will force import existing config into fresh installation.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $container_application = new ApplicationContainerExtension();

    $io = new DruDockStyle($input, $output);
    $io->section("PHP ::: drush config init");

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if ($container_application->checkForAppContainers($appname, $io)) {

      $site_settings = './app/config/sync/system.site.yml';
      $site_config = Yaml::parse(file_get_contents($site_settings));
      $uuid = $site_config['uuid'];

      $drushcmd = 'drush config-set \'system.site\' uuid ' . $uuid . ' -y';
      $command = $application->getComposePath($appname, $io) . ' exec -T php ' . $drushcmd;
      $io->info($drushcmd);
      $application->runcommand($command, $io);

      $drushcmd = 'drush cr all';
      $command = $application->getComposePath($appname, $io) . ' exec -T php ' . $drushcmd;
      $io->info($drushcmd);
      $application->runcommand($command, $io);

      $drushcmd = 'drush ev "if(\Drupal::entityManager()->getStorage(\"shortcut_set\")->load(\"default\")){\Drupal::entityManager()->getStorage(\"shortcut_set\")->load(\"default\")->delete();};"';
      $command = $application->getComposePath($appname, $io) . ' exec -T php ' . $drushcmd;
      $io->info($drushcmd);
      $application->runcommand($command, $io);

      $drushcmd = 'drush cron';
      $command = $application->getComposePath($appname, $io) . ' exec -T php ' . $drushcmd;
      $io->info($drushcmd);
      $application->runcommand($command, $io);

      $drushcmd = 'drush entity-updates -y';
      $command = $application->getComposePath($appname, $io) . ' exec -T php ' . $drushcmd;
      $io->info($drushcmd);
      $application->runcommand($command, $io);

      $drushcmd = 'drush config-import -y';
      $command = $application->getComposePath($appname, $io) . ' exec -T php ' . $drushcmd;
      $io->info($drushcmd);
      $application->runcommand($command, $io);
    }
  }
}

