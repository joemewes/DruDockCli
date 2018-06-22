<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DrushInitConfigCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Yaml\Yaml;

const SERVICE_NAME = 'php';

/**
 * Class DrushInitConfigCommand
 *
 * @package Docker\Drupal\Command
 */
class DrushInitConfigCommand extends Command {

  protected function configure() {
    $this
      ->setName('drush:init:config')
      ->setAliases(['dicg'])
      ->setDescription('Run drush config init')
      ->setHelp("This command will force import existing config into fresh installation.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
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

      $commands = [
        'drush config-set \'system.site\' uuid ' . $uuid . ' -y',
        'drush cache-rebuild',
        'drush ev "if(\Drupal::entityManager()->getStorage(\"shortcut_set\")->load(\"default\")){\Drupal::entityManager()->getStorage(\"shortcut_set\")->load(\"default\")->delete();};"',
        'drush cron',
        'drush entity-updates -y',
        'drush config-import -y',
      ];

      foreach ($commands as $cmd) {
        $command = $container_application->getComposePath($appname, $io) . ' exec -T ' . SERVICE_NAME . $cmd;
        $io->info($cmd);
        $application->runcommand($command, $io);
      }
    }
  }
}

