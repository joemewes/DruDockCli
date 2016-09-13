<?php

assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

ini_set('memory_limit','1024M');

// General sync folder.
$config_directories[CONFIG_SYNC_DIRECTORY] = '/app/repository/config/sync';

// Local settings.
$databases['default']['default'] = array(
    'database' => 'dev_db',
    'username' => 'dev',
    'password' => 'DEVPASSWORD',
    'prefix' => '',
    'host' => 'db',
    'port' => '',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
);

$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
$settings['extension_discovery_scan_tests'] = TRUE;
$settings['rebuild_access'] = FALSE;
$settings['skip_permissions_hardening'] = TRUE;
$settings['extension_discovery_scan_tests'] = FALSE;
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

$settings['trusted_host_patterns'] = array('^docker\.dev');

$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
