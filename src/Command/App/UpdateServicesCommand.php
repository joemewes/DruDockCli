<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class UpdateServicesCommand
 * @package Docker\Drupal\Command
 */
class UpdateServicesCommand extends Command {

  protected function configure() {
    $this
      ->setName('app:update:services')
      ->setAliases(['aus'])
      ->setDescription('Update APP services')
      ->setHelp("This command will update all containers from https://hub.docker.com for the current APP via the docker-compose.yml file.")
      ->addOption('services', 's', InputOption::VALUE_OPTIONAL, 'Select app services [PHP, NGINX, MYSQL, SOLR, REDIS, MAILHOG]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();
    $cfa = new ApplicationConfigExtension();
    $io = new DruDockStyle($input, $output);
    $io->section("UPDATING SERVICES");

    if ($config = $application->getAppConfig($io)) {

      $io = new DruDockStyle($input, $output);
      $io->section("Drudock ::: Update App Services");

      $currentServices = implode(',', array_keys($config['services']));
      $io->info('Current service are ' . $currentServices);

      // Ask for new services.
      $config['services'] = $cfa->getSetServices($io, $input, $output, $this);

      // Apply required versions to default config templates.
      $config['services'] = $cfa->updateConfigServiceVersions($io, $input, $output, $this, $config);

      // Write updated compose file.
      $cfa->writeDockerComposeConfig($io, $config, TRUE);

      // Write updated app .config file.
      $yaml = Yaml::dump($config);
      file_put_contents('./.config.yml', $yaml);
    }
  }
}