<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\InitBuildCommand.
 */

namespace Docker\Drupal\Command\App;

use Docker\Drupal\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DruDockStyle;


/**
 * Class InitBuildCommand
 *
 * @package Docker\Drupal\Command
 */
class InitBuildCommand extends Command {

  protected function configure() {
    $this
      ->setName('app:init:build')
      ->setAliases(['aib'])
      ->setDescription('Initialize environment and run build.')
      ->setHelp("Example : [drudock app:init:build]")
      ->addOption('appname', 'a', InputOption::VALUE_OPTIONAL, 'Specify NAME of application to build [app-dd-mm-YYYY]')
      ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]')
      ->addOption('dist', 'r', InputOption::VALUE_OPTIONAL, 'Specify app requirements [Development,Feature]')
      ->addOption('src', 'g', InputOption::VALUE_OPTIONAL, 'Specify app src [New, Git]')
      ->addOption('git', 'gs', InputOption::VALUE_OPTIONAL, 'Git repository URL')
      ->addOption('apphost', 'p', InputOption::VALUE_OPTIONAL, 'Specify preferred host path [drudock.localhost]')
      ->addOption('services', 's', InputOption::VALUE_OPTIONAL, 'Select app services [PHP, NGINX, MYSQL, SOLR, REDIS, MAILHOG]');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = new Application();

    $io = new DruDockStyle($input, $output);

    $command = $this->getApplication()->find('app:init');
    $options = [];
    $options['command'] = 'app:init';

    $inputoptions = $input->getOptions();
    if (array_key_exists('appname', $inputoptions)) {
      $options['--appname'] = $input->getOption('appname');
    }
    if (array_key_exists('appname', $inputoptions)) {
      $options['--type'] = $input->getOption('type');
    }
    if (array_key_exists('appname', $inputoptions)) {
      $options['--dist'] = $input->getOption('dist');
    }
    if (array_key_exists('appname', $inputoptions)) {
      $options['--src'] = $input->getOption('src');
    }
    if (array_key_exists('appname', $inputoptions)) {
      $options['--git'] = $input->getOption('git');
    }
    if (array_key_exists('appname', $inputoptions)) {
      $options['--apphost'] = $input->getOption('apphost');
    }
    if (array_key_exists('appname', $inputoptions)) {
      $options['--services'] = $input->getOption('services');
    }

    $setup = new ArrayInput($options);
    $command->run($setup, $output);

    $appname = $input->getOption('appname');
    $system_appname = strtolower(str_replace(' ', '', $appname));
    chdir($system_appname);

    if ($config = $application->getAppConfig($io)) {
      $appname = $config['appname'];
    }
    else {
      $appname = 'app';
    }

    $io->section("APP ::: Init " . $appname . " containers");
    $command = $this->getApplication()->find('app:build');
    $arguments = array('command' => 'app:init');
    $setup = new ArrayInput($arguments);
    $command->run($setup, $output);

  }
}
