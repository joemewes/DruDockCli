<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DockerDrupalStyle;
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

		$io = new DockerDrupalStyle($input, $output);
    $io->section("PHP ::: drush config init");

    if($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }

    if($application->checkForAppContainers($appname, $io)){

      $site_settings = './app/config/sync/system.site.yml';
      $site_config = Yaml::parse(file_get_contents($site_settings));
      $uuid = $site_config['uuid'];

      $command = $application->getComposePath($appname, $io).' exec -T php drush config-set \'system.site\' uuid ' . $uuid . ' -y';
      $application->runcommand($command, $io);

      $command = $application->getComposePath($appname, $io).' exec -T php drush cr all';
      $application->runcommand($command, $io);

      $command = $application->getComposePath($appname, $io).' exec -T php drush ev "\Drupal::entityManager()->getStorage(\"shortcut_set\")->load(\"default\")->delete();"';
      $application->runcommand($command, $io);

      $command = $application->getComposePath($appname, $io).' exec -T php drush cron';
      $application->runcommand($command, $io);

      $command = $application->getComposePath($appname, $io).' exec -T php drush entity-updates -y';
      $application->runcommand($command, $io);

      $command = $application->getComposePath($appname, $io).' exec -T php drush config-import -y';
      $application->runcommand($command, $io);
    }
	}
}
