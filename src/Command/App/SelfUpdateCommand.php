<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\SelfUpdateCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Docker\Drupal\Extension\ApplicationContainerExtension;
use Docker\Drupal\Extension\ApplicationConfigExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;

/**
 * Class SelfUpdateCommand
 *
 * @package Docker\Drupal\Command
 */
class SelfUpdateCommand extends Command
{

    protected $app;

    protected $cfa;

    protected $cta;

    protected function configure()
    {
        $this
        ->setName('self:update')
        ->setAliases(['su'])
        ->setDescription('Update Drudock version to latest released version.')
        ->setHelp("Example : [drudock self:update");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = new Application();

        $io = new DruDockStyle($input, $output);
        $io->section("Drudock ::: Updating");

        $command = 'curl -O --progress-bar https://s3.eu-west-2.amazonaws.com/drudock/drudock.phar && \
    mv drudock.phar /usr/local/bin/drudock && \
    chmod +x /usr/local/bin/drudock';
      // Do download.
        exec($command);

      // Output version.
        $dockerversion = $application->getVersion();
        $io->info(' ');
        $io->note('Drudock Version ::: ' . $dockerversion);
    }
}
