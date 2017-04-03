<?php

namespace Docker\Drupal;

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
class Application extends ParentApplication {
  /**
   * @var string
   */
  const NAME = 'Docker Drupal';

  /**
   * @var string
   */
  const VERSION = '1.3.4-alpha5';

  /**
   * @var string
   */
  protected $directoryRoot;

  public function __construct() {
    parent::__construct($this::NAME, $this::VERSION);

    $this->setDefaultTimezone();
    $this->addCommands($this->registerCommands());
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    $this->registerCommands();
    parent::doRun($input, $output);
  }

  /**
   * @return string
   */
  public function getUtilRoot() {
    return realpath(__DIR__ . '/../') . '/';
  }

  /**
   * @return string
   */
  public function getVersion() {
    return $this::VERSION;
  }


  /**
   * @return ContainerBuilder
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * Set the default timezone.
   *
   * PHP 5.4 has removed the autodetection of the system timezone,
   * so it needs to be done manually.
   * UTC is the fallback in case autodetection fails.
   */
  protected function setDefaultTimezone() {
    $timezone = 'UTC';
    if (is_link('/etc/localtime')) {
      // Mac OS X (and older Linuxes)
      // /etc/localtime is a symlink to the timezone in /usr/share/zoneinfo.
      $filename = readlink('/etc/localtime');
      if (strpos($filename, '/usr/share/zoneinfo/') === 0) {
        $timezone = substr($filename, 20);
      }
    }
    elseif (file_exists('/etc/timezone')) {
      // Ubuntu / Debian.
      $data = file_get_contents('/etc/timezone');
      if ($data) {
        $timezone = trim($data);
      }
    }
    elseif (file_exists('/etc/sysconfig/clock')) {
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
  public function dockerHealthCheck($io) {
    $names = shell_exec("echo $(docker ps --format '{{.Names}}|{{.Status}}:')");
    $n_array = explode(':', $names);
    $rows = [];
    foreach ($n_array as $i => $n) {
      $c = explode('|', $n);
      if ($c[0] && $c[1]) {
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
  public function getRunningContainerNames() {
    $names = shell_exec("echo $(docker ps --format '{{.Names}}')");
    return explode(' ', $names);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultInputDefinition() {
    return new InputDefinition(
      [
        new InputArgument('command', InputArgument::REQUIRED),
        new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
        new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
        new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages'),
        new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
        new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
        new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
        new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        //new InputOption('--yes', '-y', InputOption::VALUE_NONE, 'Answer "yes" to all prompts'),
      ]
    );
  }

  /**
   * @return \Symfony\Component\Console\Command\Command[]
   */
  protected function registerCommands() {
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
    $commands[] = new Command\UpdateConfigCommand();


    $commands[] = new Command\Mysql\MysqlImportCommand();
    $commands[] = new Command\Mysql\MysqlExportCommand();
    $commands[] = new Command\Mysql\MysqlMonitorCommand();


    $commands[] = new Command\Nginx\NginxMonitorCommand();
    $commands[] = new Command\Nginx\NginxReloadCommand();
    $commands[] = new Command\Nginx\NginxFlushPagespeedCommand();
    $commands[] = new Command\Nginx\NginxAddHostCommand();


    $commands[] = new Command\Drush\DrushCommand();
    $commands[] = new Command\Drush\DrushClearCacheCommand();
    $commands[] = new Command\Drush\DrushLoginCommand();
    $commands[] = new Command\Drush\DrushModuleEnableCommand();
    $commands[] = new Command\Drush\DrushModuleDisableCommand();
    $commands[] = new Command\Drush\DrushUpDbCommand();
    $commands[] = new Command\Drush\DrushInitConfigCommand();


    $commands[] = new Command\Redis\RedisMonitorCommand();
    $commands[] = new Command\Redis\RedisPingCommand();
    $commands[] = new Command\Redis\RedisFlushCommand();
    $commands[] = new Command\Redis\RedisInfoCommand();


    $commands[] = new Command\Behat\BehatStatusCommand();
    $commands[] = new Command\Behat\BehatMonitorCommand();
    $commands[] = new Command\Behat\BehatCommand();


    $commands[] = new Command\Sync\AppSyncMonitorCommand();


    $commands[] = new Command\Prod\ProdUpdateCommand();


    return $commands;
  }

  /**
   * @return string
   */
  public function getDockerVersion() {
    $command = 'docker --version';
    $process = new Process($command);
    $process->setTimeout(2);
    $process->run();
    return $process->getOutput();
  }

  /**
   * @return array
   */

  public function getAppConfig($io, $skip_checks = FALSE) {
    if (file_exists('.config.yml')) {
      $config = Yaml::parse(file_get_contents('.config.yml'));
      $config_keys = array_keys($config);
      $requirements = $this->getDDrequirements();

      if(!$skip_checks) {
        $missing_reqs = [];
        foreach ($requirements as $req) {
          if (!in_array($req, $config_keys)) {
            $missing_reqs[] = $req;
          }
        }

        if (count($missing_reqs) > 0) {
          $io->info('Your app is missing the following config, please run [dockerdrupal docker:update:config] : ');
          foreach ($missing_reqs as $req) {
            $io->warning($req);
          }
          exit;
        }
      }

      if (substr($this->getVersion(), 0, 1) != substr($config['dockerdrupal']['version'], 0, 1)) {
        $io->warning('You\'re installed DockerDrupal version is different to setup app version and may not work');
      }

      return $config;
    }
    else {
      $io->error('You\'re not currently in an APP directory. APP .config.yml not found.');
      exit;
    }
  }

  /**
   * @return Boolean
   */

  public function setAppConfig($config, $appname, $io) {
    $system_appname = strtolower(str_replace(' ', '', $appname));

    if (file_exists('.config.yml')) {
      $yaml = Yaml::dump($config);
      file_put_contents('.config.yml', $yaml);
      return TRUE;
    }
    else {
      $io->error('You\'re not currently in an APP directory. APP .config.yml not found.');
      exit;
    }
  }

  /**
   * @return string
   */
  public function getComposePath($appname, $io) {

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $latestbuild =  [];
    $reqs = '';
    $fs = new Filesystem();

    if ($config = $this->getAppConfig($io)) {
      if (isset($config['reqs'])) {
        $reqs = $config['reqs'];
      }
      if (isset($config['builds'])) {
        $latestbuild = $config['builds'];
      }
    }

    switch ($reqs) {
      case 'Prod':
      case 'Stage':
      $project = '--project-name=' . $system_appname . '--' . end($latestbuild);
      default:
        $project = '';
    }

    if ($fs->exists('docker-compose.yml')) {
      return 'docker-compose ';
    }
    elseif ($fs->exists('./docker_' . $system_appname . '/docker-compose.yml')) {
      return 'docker-compose -f ./docker_' . $system_appname . '/docker-compose.yml ' . $project . ' ';
    }
    else {
      $io->error("docker-compose.yml : Not Found");
      exit;
    }
  }

  /**
   * @return string
   */
  public function getDataComposePath($appname, $io) {

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $build =  [];
    $reqs = '';
    $fs = new Filesystem();

    if ($config = $this->getAppConfig($io)) {
      $reqs = $config['reqs'];
      if (is_array($config['builds'])) {
        $build = end($config['builds']);
      }
    }

    if (!$build) {
      $io->error('Build :: Config not found');
      return;
    }

    switch ($reqs) {
      case 'Prod':
        $project = '--project-name=data';
      case 'Stage':
        $project = '--project-name=' . $system_appname . '_data';
      default:
        $project = '';
    }

    if (!$project) {
      // @todo: This message needs review.
      // @see https://github.com/4AllDigital/DockerDrupalCli/issues/91
      $io->error("docker-compose-data.yml : Not Found");
      exit;
    }

    if ($fs->exists('./docker_' . $system_appname . '/docker-compose-data.yml')) {
      return'docker-compose -f ./docker_' . $system_appname . '/docker-compose-data.yml ' . $project . ' ';
    }
    else {
      $io->error("docker-compose-data.yml : Not Found");
      exit;
    }
  }

  /**
   * @return string
   */
  public function getProxyComposePath($appname, $io) {

    $system_appname = strtolower(str_replace(' ', '', $appname));

    if ($config = $this->getAppConfig($io)) {
      $reqs = $config['reqs'];
    }

    $fs = new Filesystem();

    if (isset($reqs) && $reqs == 'Prod') {
      $project = '--project-name=proxy';
    }
    else {
      $io->error("docker-compose-data.yml : Not Found");
      exit;
    }

    if ($fs->exists('./docker_' . $system_appname . '/docker-compose-nginx-proxy.yml')) {
      return 'docker-compose -f ./docker_' . $system_appname . '/docker-compose-nginx-proxy.yml ' . $project . ' ';
    }
    else {
      $io->error("docker-compose-data.yml : Not Found");
      exit;
    }
  }

  /**
   * @return Boolean
   */
  public function checkForAppContainers($appname, $io) {

    $system_appname = strtolower(str_replace(' ', '', $appname));
    // Check for standard app containers
    if (exec($this->getComposePath($appname, $io) . 'ps | grep ' . preg_replace("/[^A-Za-z0-9 ]/", '', $system_appname))) {
      return TRUE;
    }
    else {
      $io->warning("APP has no containers, try running `dockerdrupal build:init --help`");
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
    $process->setTty(TRUE);
    $process->run(function ($type, $buffer) {
      global $output;
      if ($output) {
        $output->info($buffer);
      }
    });

    // Hack to ignore docker-compose exec exit code when using Tty
    // https://github.com/docker/compose/issues/3379.
    if (!$process->isSuccessful() && ($process->getExitCode() != 129)) {
      throw new ProcessFailedException($process);
    }
  }

  /**
   * @return string
   */
  function setNginxHost($io) {

    if ($config = $this->getAppConfig($io)) {
      $appname = $config['appname'];
      $apphost = $config['host'];
      $reqs = $config['reqs'];
    }

    if (!isset($apphost)) {
      $apphost = 'docker.dev';
    }

    $system_appname = strtolower(str_replace(' ', '', $appname));
    $nginxconfig = 'server {
    listen   80;
    listen   [::]:80;

    index index.php index.html;
    server_name ' . $apphost . ';
    error_log  /var/log/nginx/app-error.log;
    access_log /var/log/nginx/app-access.log;
    root /app/www;

    ## GENERIC
    sendfile off;

    client_max_body_size 20M;

    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    location = /robots.txt {
        allow all;
        log_not_found off;
        access_log off;
    }

    # Very rarely should these ever be accessed outside of your lan
    location ~* \.(txt|log)$ {
        allow 192.168.0.0/16;
        deny all;
    }

    location ~ \..*/.*\.php$ {
        return 403;
    }

    location ~ ^/sites/.*/private/ {
        return 403;
    }

    # Allow "Well-Known URIs" as per RFC 5785
    location ~* ^/.well-known/ {
        allow all;
    }

    # Block access to "hidden" files and directories whose names begin with a
    # period. This includes directories used by version control systems such
    # as Subversion or Git to store control files.
    location ~ (^|/)\. {
        return 403;
    }

    location @drupal {
        rewrite ^/(.*)$ /index.php?q=$1 last;
    }

    location / {
        try_files $uri @drupal;
    }

    # Don\'t allow direct access to PHP files in the vendor directory.
    location ~ /vendor/.*\.php$ {
        deny all;
        return 404;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param REMOTE_ADDR $http_x_real_ip;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_cache  off;
        fastcgi_intercept_errors on;
        fastcgi_hide_header \'X-Drupal-Cache\';
        fastcgi_hide_header \'X-Generator\';

    }

    location @rewrite {
        rewrite ^/(.*)$ /index.php?q=$1;
    }

    # Fighting with Styles? This little gem is amazing.
    location ~ ^/sites/.*/files/styles/ { # For Drupal >= 7
        try_files $uri @rewrite;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico)$ {
        expires max;
        log_not_found off;
    }
}';

    if ($reqs == 'Prod') {
      file_put_contents('./docker_' . $system_appname . '/mounts/sites-enabled/' . $apphost, $nginxconfig);

      $nginxenv = "VIRTUAL_HOST=$apphost
APPS_PATH=~/app
VIRTUAL_NETWORK=nginx-proxy";

      file_put_contents('./docker_' . $system_appname . '/nginx.env', $nginxenv);

    }
    elseif ($reqs == 'Stage') {
      file_put_contents('./docker_' . $system_appname . '/mounts/sites-enabled/' . $apphost, $nginxconfig);

      $nginxenv = "VIRTUAL_HOST=$apphost
APPS_PATH=~/app
VIRTUAL_NETWORK=nginx-proxy";

      file_put_contents('./docker_' . $system_appname . '/nginx.env', $nginxenv);
    }
    else {
      file_put_contents('./docker_' . $system_appname . '/sites-enabled/docker.dev', $nginxconfig);
    }
  }

  /**
   * @param $application
   * @param $io
   */
  public function addHostConfig($newhost, $io, $update = FALSE) {
    // Add initial entry to hosts file.
    // OSX @TODO update as command for all systems and OS's.
    $utilRoot = $this->getUtilRoot();

    $ip = '127.0.0.1';

    if ($update && $config = $this->getAppConfig($io)) {
      $apphost = $config['host'];
      $appname = $config['appname'];
      $system_appname = strtolower(str_replace(' ', '', $appname));
    }
    else {
      $apphost = 'docker.dev';
    }

    if ($update) {
      $hosts_file = '/etc/hosts';
      $app_host_config = "### " . $system_appname . "\n" . $ip . " " . $apphost . "\n###";
      $new_host_config = "### " . $system_appname . "\n" . $ip . " " . $newhost . "\n###";
      $hosts_file_contents = file_get_contents($hosts_file);

      if (!strpos($hosts_file_contents, $app_host_config)) {
        // Add new.
        $command = sprintf("echo '%s' | sudo tee -a %s >/dev/null", $new_host_config, $hosts_file);
        $this->runcommand($command, $io, TRUE);
      }
      else {
        // Replace existing.
        $hosts_file_contents = str_replace($app_host_config, $new_host_config, $hosts_file_contents);
        $command = 'echo "' . $hosts_file_contents . '" | sudo tee ' . $hosts_file;
        exec($command);
      }
    }else{
      $hosts_file = '/etc/hosts';
      $apphost = 'docker.dev';
      $command = sprintf("echo '%s %s' | sudo tee -a %s >/dev/null", $ip, $apphost, $hosts_file);
      $this->runcommand($command, $io, TRUE);
    }

    if (!file_exists('/Library/LaunchDaemons/com.4alldigital.dockerdrupal.plist')) {
      $command = 'sudo cp -R ' . $utilRoot . '/bundles/osx/com.4alldigital.dockerdrupal.plist /Library/LaunchDaemons/com.4alldigital.dockerdrupal.plist';
      $this->runcommand($command, $io, TRUE);
    }
  }

  /**
   * @return string
   */
  public function checkDocker($io) {
    $command = 'docker info';
    $process = new Process($command);
    $process->setTimeout(2);
    $process->run();
    if (!$process->isSuccessful()) {
      if ($showoutput) {
        $out = 'Can\'t connect to Docker. Is it running?';
        $io->warning($out);
      }
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * @return string
   */
  function getOs() {
    return PHP_OS;
  }

  function requireUpdate($io) {

    $io->warning('This app .config.yml is out of date and missing data. Please run [dockerdrupal up:config].');
    exit;
  }

  /**
   * @return array
   *  Return keys of current required app config.
   */
  function getDDrequirements() {
    return [
      'appname',
      'apptype',
      'host',
      'reqs',
      'appsrc',
      'repo',
    ];
  }

  /**
   * Set currently assigned config into yaml format and file.
   */
  function setConfig($config) {
    $yaml = Yaml::dump($config);
    file_put_contents('.config.yml', $yaml);
  }

}
