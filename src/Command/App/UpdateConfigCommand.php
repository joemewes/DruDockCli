<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\UpdateConfigCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class UpdateConfigCommand
 *
 * @package Docker\Drupal\Command
 */
class UpdateConfigCommand extends Command
{

    const QUESTION = 'question';

    protected function configure()
    {
        $this
        ->setName('app:update:config')
        ->setAliases(['aucg'])
        ->setDescription('Update APP config')
        ->setHelp('This command will update all .config.yaml to include current drudock config requirements.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();
        $config_application = new ApplicationConfigExtension();
        $io = new DruDockStyle($input, $output);
        $io->section('APP ::: UPDATING CONFIG');

        $config = $application->getAppConfig($io, '', true);

        if (!isset($config['appname'])) {
            $config['appname'] = $config_application->getSetAppname($io, $input, $output, $this);
        }

        if (!isset($config['host'])) {
            $config['host'] = $config_application->getSetHost($io, $input, $output, $this);
        }

        if (!isset($config['dist'])) {
            $config['dist'] = $config_application->getSetDistribution($io, $input, $output, $this);
        }

        if (!isset($config['src'])) {
            $src = $config_application->getSetSource($io, $input, $output, $this);
            if ($src === 'Git') {
                $gitrepo = $config_application->getSetSCMSource($io, $input, $output, $src, $this);
            } else {
                $gitrepo = '';
            }
            $config['src'] = $src;
            $config['repo'] = $gitrepo;
        }

        if (!isset($config['apptype'])) {
            $config['apptype'] = $config_application->getSetType($io, $input, $output, $this);
        }

        $application->setConfig($config);
        $io->info('  App config all up to date.');
        $io->info('  ');
    }
}
