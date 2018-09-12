<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DrushRegistryRebuildCommand.
 */

namespace Docker\Drupal\Command\Drush;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class DrushRegistryRebuildCommand
 *
 * @package \Docker\Drupal\Command\DrushRegistryRebuildCommand
 */
class DrushRegistryRebuildCommand extends Command
{

    private $run = true;
    protected $application;
    protected $containerApplication;
    protected $io;
    protected $config;

    protected function configure()
    {
        $this
        ->setName('drush:rr')
        ->setAliases(['drr'])
        ->setDescription('Run drush registry rebuild')
        ->setHelp("This command will rebuild the Drupal APP registry.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->run) {
            return;
        }

        $this->io->info('Rebuilding registry.');
        $command = $this->containerApplication->getComposePath($this->config['appname'], $this->io) . ' exec -T php drush rr';
        $this->application->runcommand($command, $this->io);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->application = new Application();
        $this->containerApplication = new ApplicationContainerExtension();
        $this->io = new DruDockStyle($input, $output);
        $this->config = $this->application->getAppConfig($this->io);

        $this->io->section('PHP ::: drush rr');

        if ($this->config['apptype'] !== 'D7') {
            $this->run = false;
            $this->io->error('Registry rebuild is only available on Drupal 7.');
            return;
        }

        $appname = $this->config['appname'];

        if (!$this->containerApplication->checkForAppContainers($appname, $this->io)) {
            $this->run = false;
            return;
        }

      // @todo: Configuration should be abstracted.
        if (!array_key_exists('settings', $this->config)) {
            $this->config['settings'] = [];
        }
        if (!array_key_exists('drush', $this->config['settings'])) {
            $this->config['settings']['drush'] = [];
        }
        if (!array_key_exists('registryRebuild', $this->config['settings']['drush'])) {
            $this->config['settings']['drush']['registryRebuild'] = false;
        }

        $this->io->info('Checking dependencies.');
        if (!$this->config['settings']['drush']['registryRebuild']) {
            $this->io->info('Installing Registry rebuild.');
            $command = $this->containerApplication->getComposePath($appname, $this->io) . ' exec -T php drush @none dl registry_rebuild-7.x -y';
            $this->application->runcommand($command, $this->io);
            $this->config['settings']['drush']['registryRebuild'] = true;
            $this->application->setAppConfig($this->config, $this->io);
        } else {
            $this->io->info('Registry rebuild is already installed.');
        }
    }
}
