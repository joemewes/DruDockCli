<?php

namespace Docker\Drupal;

use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Application as ParentApplication;


/**
* Class Application
* @package Docker\Drupal
*/
class Application extends ParentApplication
{
  /**
   * @var string
   */
  const NAME = 'Docker Drupal';

  /**
   * @var string
   */
  const VERSION = '1.0.10';

  /**
   * @var string
   */
  protected $directoryRoot;

  public function __construct()
  {
      parent::__construct( $this::NAME, $this::VERSION);

      $this->setDefaultTimezone();
      $this->addCommands($this->registerCommands());
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(InputInterface $input, OutputInterface $output)
  {
      $this->registerCommands();
      parent::doRun($input, $output);
  }

  /**
   * @return string
   */
  public function getUtilRoot()
  {
      $utilRoot = realpath(__DIR__.'/../') . '/';
      return $utilRoot;
  }

  /**
   * @return string
   */
  public function getVersion()
  {
      return $this::VERSION;
  }


  /**
   * @return ContainerBuilder
   */
  public function getContainer()
  {
      return $this->container;
  }

  /**
   * Set the default timezone.
   *
   * PHP 5.4 has removed the autodetection of the system timezone,
   * so it needs to be done manually.
   * UTC is the fallback in case autodetection fails.
   */
  protected function setDefaultTimezone()
  {
      $timezone = 'UTC';
      if (is_link('/etc/localtime')) {
          // Mac OS X (and older Linuxes)
          // /etc/localtime is a symlink to the timezone in /usr/share/zoneinfo.
          $filename = readlink('/etc/localtime');
          if (strpos($filename, '/usr/share/zoneinfo/') === 0) {
              $timezone = substr($filename, 20);
          }
      } elseif (file_exists('/etc/timezone')) {
          // Ubuntu / Debian.
          $data = file_get_contents('/etc/timezone');
          if ($data) {
              $timezone = trim($data);
          }
      } elseif (file_exists('/etc/sysconfig/clock')) {
          // RHEL/CentOS
          $data = parse_ini_file('/etc/sysconfig/clock');
          if (!empty($data['ZONE'])) {
              $timezone = trim($data['ZONE']);
          }
      }

      date_default_timezone_set($timezone);
  }

  /**
   * @output status table
   */
  public function dockerHealthCheck($io){
      $names = shell_exec("echo $(docker ps --format '{{.Names}}|{{.Status}}:')");
      $n_array = explode(':',$names);
      $rows = [];
      foreach($n_array as $i => $n){
          $c = explode('|', $n);
          if($c[0] && $c[1]) {
              $rows[$i]['Name'] = str_replace(' ', '', $c[0]);
              $rows[$i]['Status'] = $c[1];
          }
      }
      $headers = ['Container Name', 'Status'];
      $io->table($headers, $rows);
  }

  /**
   * @return array
   */
  public function getRunningContainerNames(){
      $names = shell_exec("echo $(docker ps --format '{{.Names}}')");
      $n_array = explode(' ',$names);
      return $n_array;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultInputDefinition()
  {
    return new InputDefinition(
      [
        new InputArgument('command', InputArgument::REQUIRED),
        new InputOption('--help', '-h', InputOption::VALUE_NONE),
        new InputOption('--quiet', '-q', InputOption::VALUE_NONE),
        new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE),
        new InputOption('--version', '-V', InputOption::VALUE_NONE),
        new InputOption('--ansi', '', InputOption::VALUE_NONE),
        new InputOption('--no-ansi', '', InputOption::VALUE_NONE),
        new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE),
      ]
    );
  }

  /**
   * @return \Symfony\Component\Console\Command\Command[]
   */
  protected function registerCommands()
  {
    static $commands = [];
    if (count($commands)) {
        return $commands;
    }

    $commands[] = new Command\InitCommand();
    $commands[] = new Command\BuildCommand();
    $commands[] = new Command\StopCommand();
    $commands[] = new Command\StartCommand();
    $commands[] = new Command\RestartCommand();
    $commands[] = new Command\DestroyCommand();
    $commands[] = new Command\StatusCommand();
    $commands[] = new Command\ExecCommand();
    $commands[] = new Command\AboutCommand();
		$commands[] = new Command\UpdateCommand();

		$commands[] = new Command\Mysql\MysqlImportCommand();
		$commands[] = new Command\Mysql\MysqlExportCommand();
		$commands[] = new Command\Mysql\MysqlMonitorCommand();

		$commands[] = new Command\Drush\DrushCommand();
		$commands[] = new Command\Drush\DrushClearCacheCommand();
		$commands[] = new Command\Drush\DrushLoginCommand();
		$commands[] = new Command\Drush\DrushModuleEnableCommand();

    $commands[] = new Command\Redis\RedisMonitorCommand();
    $commands[] = new Command\Redis\RedisPingCommand();
    $commands[] = new Command\Redis\RedisFlushCommand();
		$commands[] = new Command\Redis\RedisInfoCommand();

		$commands[] = new Command\Sync\AppSyncMonitorCommand();

    return $commands;
  }

  /**
   * @return string
   */
  public function checkDocker($io, $showoutput)
  {
      $command = 'docker info';
      $process = new Process($command);
      $process->setTimeout(2);
      $process->run();

      if (!$process->isSuccessful()) {
          if($showoutput) {
              $out = 'Can\'t connect to Docker. Is it running?';
              $io->warning($out);
          }
          return false;
      }else{
          return true;
      }
  }

  /**
   * @return string
   */
  public function getDockerVersion(){
      $command = 'docker --version';
      $process = new Process($command);
      $process->setTimeout(2);
      $process->run();
      $version = $process->getOutput();
      return $version;
  }

  /**
   * @return array
   */
  public function getAppConfig($io){
    if(file_exists('.config.yml')){
      $config = Yaml::parse(file_get_contents('.config.yml'));
      $dockerdrupal_version = $config['dockerdrupal']['version'];
      if($dockerdrupal_version != $this->getVersion()){
          $io->warning('You\'re installed DockerDrupal version is different to setup app version and may not work');
      }
      return $config;
    }else{
      $io->error('You\'re not currently in an APP directory. APP .config.yml not found.');
      exit;
    }
  }

  /**
   * @return string
   */
  public function getComposePath($appname, $io){

    $fs = new Filesystem();
    if($fs->exists('docker-compose.yml')) {
      $dc = 'docker-compose ';
      return $dc;
    }elseif($fs->exists('./docker_'.$appname.'/docker-compose.yml')){
      $dc = 'docker-compose -f ./docker_'.$appname.'/docker-compose.yml ';
      return $dc;
    }else{
      $io->error("docker-compose.yml : Not Found");
      exit;
    }
  }

  /**
   * @return Boolean
   */
  public function checkForAppContainers($appname, $io){

    if(exec($this->getComposePath($appname, $io).'ps | grep '.str_replace('_', '', $appname))) {
      return TRUE;
    }else{
      $io->warning("APP has no containers, try running `dockerdrupal build:init --help`");
      exit;
    }
  }

	/**
	 * @return string
	 */
	public function runcommand($command, $io) {

		global $output;
		$output = $io;

		$process = new Process($command);
		$process->setTimeout(3600);
		$process->run(function ($type, $buffer) {
			global $output;
			if ($output) {
				$output->info($buffer);
			}
		});

		if (!$process->isSuccessful()) {
			throw new ProcessFailedException($process);
		}
	}
}
