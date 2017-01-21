<?php

/**
 * @file
 * Contains \Docker\Drupal\Command\DemoCommand.
 */

namespace Docker\Drupal\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Docker\Drupal\Style\DockerDrupalStyle;
use Symfony\Component\Yaml\Yaml;


/**
 * Class DemoCommand
 * @package Docker\Drupal\ContainerAwareCommand
 */
class InitCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('env:init')
      ->setAliases(['env'])
      ->setDescription('Fetch and build DockerDrupal containers')
      ->setHelp('This command will fetch the specified DockerDrupal config, download and build all necessary images.  NB: The first time you run this command it will need to download 4GB+ images from DockerHUB so make take some time.  Subsequent runs will be much quicker.')
      ->addArgument('appname', InputArgument::OPTIONAL, 'Specify NAME of application to build [app-dd-mm-YYYY]')
      ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Specify app version [D7,D8,DEFAULT]')
      ->addOption('reqs', 'r', InputOption::VALUE_OPTIONAL, 'Specify app requirements [Basic,Full,Prod]')
      ->addOption('appsrc', 's', InputOption::VALUE_OPTIONAL, 'Specify app src [New, Git]')
      ->addOption('git', 'g', InputOption::VALUE_OPTIONAL, 'Git repository URL')
      ->addOption('apphost', 'p', InputOption::VALUE_OPTIONAL, 'Specify preferred host path [docker.dev]');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $application = $this->getApplication();
    $utilRoot = $application->getUtilRoot();

    $io = new DockerDrupalStyle($input, $output);

    // check Docker is running
    $application->checkDocker($io, TRUE);

    $fs = new Filesystem();
    $date =  date('Y-m-d--H-i-s');

    // check if this folder is has APP config
    if(file_exists('.config.yml')){
      $io->error('You\'re currently in an APP directory');
      return;
    }

    if($application->getOs() == 'Darwin') {

      $message = "If prompted, please type admin password to add '127.0.0.1 docker.dev' to /etc/hosts \n && COPY ifconfig alias.plist to /Library/LaunchDaemons/";
      $io->note($message);

      $application->addHostConfig($io, FALSE);
    }

    // GET AND SET APPNAME.
    $appname = $input->getArgument('appname');
    if (!$appname) {
      $io->title("SET APP NAME");
      $helper = $this->getHelper('question');
      $question = new Question('Enter App name [dockerdrupal_app_' . $date . '] : ', 'my-app-' . $date);
      $appname = $helper->ask($input, $output, $question);
    }

    // GET AND SET APP SOURCE.
    $src = $input->getOption('appsrc');
    $available_src = array('New', 'Git');

    if ($src && !in_array($src, $available_src)) {
      $io->warning('APP SRC : ' . $src . ' not allowed.');
      $src = NULL;
    }

    if (!$src) {
      $io->info(' ');
      $io->title("SET APP SOURCE");
      $helper = $this->getHelper('question');
      $question = new ChoiceQuestion(
        'Is this app a new build or loaded from a remote GIT repository [New, Git] : ',
        $available_src,
        'New'
      );
      $src = $helper->ask($input, $output, $question);
    }

    // GET AND SET APP SOURCE.
    $repo = $input->getOption('git');

    if (!$repo) {
      $io->title("SET APP GIT URL");
      $helper = $this->getHelper('question');
      $question = new Question('Enter remote GIT url [https://github.com/<me>/<myapp>.git] : ');
      $gitrepo = $helper->ask($input, $output, $question);
    }else{
      $gitrepo = '';
    }

    // GET AND SET APP REQUIREMENTS.
    $reqs = $input->getOption('reqs');
    $available_reqs = array('Basic', 'Full', 'Prod');

    if ($reqs && !in_array($reqs, $available_reqs)) {
      $io->warning('REQS : ' . $reqs . ' not allowed.');
      $reqs = NULL;
    }

    if (!$reqs) {
      $io->info(' ');
      $io->title("SET APP REQS");
      $helper = $this->getHelper('question');
      $question = new ChoiceQuestion(
        'Select your APP reqs [basic] : ',
        $available_reqs,
        'basic'
      );
      $reqs = $helper->ask($input, $output, $question);
    }

    // GET AND SET APP TYPE.
    $type = $input->getOption('type');
    $available_types = array('DEFAULT', 'D7', 'D8');

    if ($type && !in_array($type, $available_types)) {
      $io->warning('TYPE : ' . $type . ' not allowed.');
      $type = NULL;
    }

    if (!$type) {
      $io->info(' ');
      $io->title("SET APP TYPE");
      $helper = $this->getHelper('question');
      $question = new ChoiceQuestion(
        'Select your APP type [0] : ',
        $available_types,
        '0'
      );
      $type = $helper->ask($input, $output, $question);
    }

    // GET AND SET APP PREFERRED HOST.
    $apphost = $input->getOption('apphost');

    if (!$apphost) {
      $io->info(' ');
      $io->title("SET APP HOSTNAME");
      $helper = $this->getHelper('question');
      $question = new Question('Enter preferred app hostname [docker.dev] : ');
      $apphost = $helper->ask($input, $output, $question);
    }

    $system_appname = strtolower(str_replace(' ', '', $appname));

    if (!$fs->exists($system_appname)) {
      $fs->mkdir($system_appname, 0755);
      $fs->mkdir($system_appname . '/docker_' . $system_appname, 0755);
    }
    else {
      $io->error('This app already exists');
      return;
    }

    switch ($reqs) {
      case 'Basic':
        $fs->mirror($utilRoot . '/bundles/dockerdrupal-lite/', $system_appname . '/docker_' . $system_appname);
        if (!$apphost) { $apphost = 'docker.dev'; }

        break;

      case 'Full':
        $fs->mirror($utilRoot . '/bundles/dockerdrupal/', $system_appname . '/docker_' . $system_appname);
        if (!$apphost) { $apphost = 'docker.dev'; }

        break;

      case 'Prod':
        $fs->mirror($utilRoot . '/bundles/dockerdrupal-prod/', $system_appname . '/docker_' . $system_appname);
        if (!$apphost) { $apphost = 'docker.prod'; }

        // Set build path.
        $composebuild = Yaml::parse(file_get_contents($system_appname . '/docker_' . $system_appname . '/docker-compose.yml'));
        $composebuild['services']['app']['build']['dockerfile'] = './docker_' . $system_appname . '/build/Dockerfile';

        $composebuild['networks']['proxy']['external']['name'] = 'proxy_' . $system_appname . '_proxy';
        $composebuild['networks']['database']['external']['name'] = 'data_' . $system_appname . '_data';


        $composeconfig = Yaml::dump($composebuild);
        file_put_contents($system_appname . '/docker_' . $system_appname . '/docker-compose.yml', $composeconfig);

        // set proxy network name
        $proxynet = Yaml::parse(file_get_contents($system_appname . '/docker_' . $system_appname . '/docker-compose-nginx-proxy.yml'));
        $proxynet['services']['nginx-proxy']['networks'] = [
          $system_appname . '_proxy'
        ];
        unset($proxynet['networks']['nginx']);
        $proxynet['networks'][$system_appname . '_proxy'] = [
          'driver' => 'bridge'
        ];

        $proxynetconfig = Yaml::dump($proxynet);
        file_put_contents($system_appname . '/docker_' . $system_appname . '/docker-compose-nginx-proxy.yml', $proxynetconfig);

        // Set database name.
        $database = Yaml::parse(file_get_contents($system_appname . '/docker_' . $system_appname . '/docker-compose-data.yml'));

        unset($database['networks']['data']);
        $database['networks'][$system_appname . '_data'] = [
          'driver' => 'bridge'
        ];

        $database['services']['db']['networks'] = [$system_appname . '_data'];
        $database['services']['solr']['networks'] = [$system_appname . '_data'];
        $database['services']['redis']['networks'] = [$system_appname . '_data'];

        $databaseconfig = Yaml::dump($database);
        file_put_contents($system_appname . '/docker_' . $system_appname . '/docker-compose-data.yml', $databaseconfig);

        break;

      default:
        $fs->mirror($utilRoot . '/bundles/dockerdrupal-lite/', $system_appname . '/docker_' . $system_appname);
        if (!$apphost) { $apphost = 'docker.dev'; }

        break;
    }

    // SETUP APP CONFIG FILE.
    $config = array(
      'appname' => $appname,
      'apptype' => $type,
      'host'=> $apphost,
      'reqs' => $reqs,
      'appsrc' => $src,
      'repo' =>  $gitrepo ? $gitrepo : '',
      'created' => $date =  date('Y-m-d--H-i-s'),
      'builds' => [
        $date =  date('Y-m-d--H-i-s'),
      ],
      'dockerdrupal' => array('version' => $application->getVersion(), 'date' => $date),
    );

    $yaml = Yaml::dump($config);
    file_put_contents($system_appname . '/.config.yml', $yaml);

    $message = 'Fetching DockerDrupal v' . $application->getVersion();
    $io->info(' ');
    $io->note($message);

    $this->getDockerDrupal($application, $io, $system_appname);

    $io->info(' ');
    $io->section("DockerDrupal ::: Ready");
    $info = 'Go to app directory [cd ' . $system_appname . '] and run [dockerdrupal build:init]';
    $io->info($info);
    $io->info(' ');
  }

  /**
   * @param $application
   * @param $io
   * @param $appname
   */
  private function getDockerDrupal($application, $io, $appname) {

    if (exec('docker ps -q 2>&1', $exec_output)) {
      $dockerstopcmd = 'docker stop $(docker ps -q)';
      $application->runcommand($dockerstopcmd, $io, TRUE);
    }

    $message = 'Download and configure DockerDrupal.... This may take a few minutes....';
    $io->note($message);

    $dockerlogs = 'docker-compose -f ' . $appname . '/docker_' . $appname . '/docker-compose.yml logs -f';
    $application->runcommand($dockerlogs, $io, TRUE);

    if (exec('docker ps -q 2>&1', $exec_output)) {
      $dockercmd = 'docker kill $(docker ps -q)';
      $application->runcommand($dockercmd, $io, TRUE);
    }

    $dockercmd = 'docker-compose -f ' . $appname . '/docker_' . $appname . '/docker-compose.yml pull';
    $application->runcommand($dockercmd, $io, TRUE);
  }
}
