<?php

namespace Docker\Drupal;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Process\Process;
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
    const VERSION = '1.0.1';

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
        $commands[] = new Command\StopCommand();
        $commands[] = new Command\StartCommand();
        $commands[] = new Command\RestartCommand();
        $commands[] = new Command\DestroyCommand();
        $commands[] = new Command\StatusCommand();
        $commands[] = new Command\ExecCommand();
        $commands[] = new Command\AboutCommand();

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
}