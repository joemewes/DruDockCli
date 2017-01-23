<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docker\Drupal\Style\DockerDrupalStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;



/**
 * Class UpdateConfigCommand
 * @package Docker\Drupal\Command
 */
class UpdateConfigCommand extends Command {

  protected function configure() {
    $this
      ->setName('docker:update:config')
      ->setAliases(['up:cg'])
      ->setDescription('Update APP config')
      ->setHelp("This command will update all .config.yaml to include current dockerdrupal config requirements.");
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $io = new DockerDrupalStyle($input, $output);
    $io->section("APP ::: UPDATING CONFIG");

    $config = $application->getAppConfig($io);
    $requirements = $application->getDDrequirements();

    foreach($requirements as $req) {

      if(!isset($config[$req])) {

        if ($req == 'appname') {
          $io->title("SET APP NAME");
          $helper = $this->getHelper('question');
          $question = new Question('Enter App name [dockerdrupal_app_' . $date . '] : ', 'my-app-' . $date);
          $appname = $helper->ask($input, $output, $question);
          $config[$req] = $appname;
        }

        if ($req == 'host') {
          $io->info(' ');
          $io->title("SET APP HOSTNAME");
          $helper = $this->getHelper('question');
          $question = new Question('Enter preferred app hostname [docker.dev] : ');
          $apphost = $helper->ask($input, $output, $question);
          $config[$req] = $apphost;
        }

        if ($req == 'reqs') {
          $available_reqs = ['Basic', 'Full', 'Prod'];
          $io->info(' ');
          $io->title("SET APP REQS");
          $helper = $this->getHelper('question');
          $question = new ChoiceQuestion(
            'Select your APP reqs [basic] : ',
            $available_reqs,
            'basic'
          );
          $reqs = $helper->ask($input, $output, $question);
          $config[$req] = $reqs;
        }

        if ($req == 'appsrc') {

          $available_src = ['New', 'Git'];

          $io->info(' ');
          $io->title("SET APP SOURCE");
          $helper = $this->getHelper('question');
          $question = new ChoiceQuestion(
            'Is this app a new build or loaded from a remote GIT repository [New, Git] : ',
            $available_src,
            'New'
          );
          $src = $helper->ask($input, $output, $question);

          if ($src == 'Git') {
            $io->title("SET APP GIT URL");
            $helper = $this->getHelper('question');
            $question = new Question('Enter remote GIT url [https://github.com/<me>/<myapp>.git] : ');
            $gitrepo = $helper->ask($input, $output, $question);
          }
          else {
            $gitrepo = '';
          }
          $config[$req] = $src;
          $config['repo'] = $gitrepo;
        }

        if ($req == 'apptype') {

          $available_types = ['DEFAULT', 'D7', 'D8'];
          $io->info(' ');
          $io->title("SET APP TYPE");
          $helper = $this->getHelper('question');
          $question = new ChoiceQuestion(
            'Select your APP type [0] : ',
            $available_types,
            '0'
          );
          $type = $helper->ask($input, $output, $question);
          $config[$req] = $type;

        }

      }
    }

    $application->setConfig($config);

    $io->info("  App config all up to date.");
    $io->info("  ");
  }

}