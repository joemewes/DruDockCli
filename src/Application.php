<?php

namespace Docker\Drupal;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct( $this::NAME, $this::VERSION);

        $this->setDefaultTimezone();
        $this->addCommands($this->registerCommands());
       // $this->addOptions();
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
     * @return \Symfony\Component\Console\Command\Command[]
     */
    protected function registerCommands()
    {
        static $commands = [];
        if (count($commands)) {
            return $commands;
        }

        $commands[] = new Command\DemoCommand();
        $commands[] = new Command\StopCommand();

        return $commands;
    }
}