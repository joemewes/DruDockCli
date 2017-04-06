<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Docker\Drupal\Application;


// if you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

set_time_limit(0);
$dockerDrupalRoot = realpath(__DIR__.'/../') . '/';
$root = getcwd() . '/';

ini_set('display_errors', 1);
ini_set('log_errors', 0);

if (version_compare(PHP_VERSION, '5.5.9', '<')) {
    printf("This tool requires at least PHP 5.5.9. You currently have %s installed. Please upgrade your PHP version.\n", PHP_VERSION);
    exit(1);
}

/**
 * @var Composer\Autoload\ClassLoader $loader
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    $loader = require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
    // we are globally installed via Composer
    $loader = require __DIR__ . '/../../../autoload.php';
} else {
    echo "Composer autoload file not found.\n";
    echo "You need to run 'composer install'.\n";
    exit(1);
}

$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], getenv('SYMFONY_ENV') ?: 'dev');
$debug = getenv('SYMFONY_DEBUG') !== '0' && !$input->hasParameterOption(['--no-debug', '']) && $env !== 'prod';

//if ($debug) {
//   Debug::enable();
//}

$application = new Application('DockerDrupal Console Application', '1.0');
$application->setDefaultCommand('about');
$application->run($input);
