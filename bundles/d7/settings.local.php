<?php

####################
## DATABASE CONFIG
####################
$databases['default']['default'] = array(
    'driver' => 'mysql',
    'host' => 'db',
    'username' => 'dev',
    'password' => 'DEVPASSWORD',
    'database' => 'dev_db',
    'prefix' => '',
);

###############
##   $VARS   ##
###############
$update_free_access = FALSE;
$drupal_hash_salt = 'qV44KZY01vXbW8tnAqUy6O4MvEXUGIz8gaUZfkwkYdY';

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

####################
## DEVELOPMENT CONFIG
####################

## MEMORY BOOST
ini_set('memory_limit','1024M');
$conf['env'] = 'dev';

$conf['404_fast_paths_exclude'] = '/\/(?:nuffin)\//';
$conf['404_fast_paths'] = '/\.(?:txt|png|gif|jpg|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';
$conf['404_fast_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>';

// always override local GA
$conf['googleanalytics_account'] = 'UA-11111111-1';
// fix default D7 status warnings
$conf['drupal_http_request_fails'] = FALSE;
// secure default media config
$conf['media_youtube__secure'] = TRUE;

#######################################################################
## TOGGLE CACHE CONFIG BY COMMENTING AND UNCOMMENTING FOLLOWING BLOCKS
#######################################################################

$conf['cache'] = 0;
$conf['block_cache'] = 0;
$conf['cache_lifetime'] = 0;
$conf['page_cache_maximum_age'] = 0;
$conf['page_compression'] = 0;
$conf['preprocess_js'] = 0;
$conf['preprocess_css'] = 0;

// $conf['cache'] = 1;
// $conf['block_cache'] = 1;
// $conf['cache_lifetime'] = 84600;
// $conf['page_cache_maximum_age'] = 160000;
// $conf['page_compression'] = 1;
// $conf['preprocess_js'] = 1;
// $conf['preprocess_css'] = 1;

####################
## SEARCH API HOOKUP
####################
$conf['search_api_override_mode'] = 'load';
$conf['search_api_override_servers'] = array(
    'solr' => array(
        'name' => 'THE Solr Server (overridden)',
        'options' => array(
            'host' => 'solr',
            'port' => '8983',
            'path' => '/solr/SITE'
        ),
    ),
);

// REDIS config
####################
## REDIS CONFIG
####################
/**
 * Uncomment after enabling REDIS and populate 'cache_prefix' value to match your app
 */
//$conf['lock_inc'] = 'sites/all/modules/contrib/redis/redis.lock.inc';
//$conf['path_inc'] = 'sites/all/modules/contrib/redis/redis.path.inc';
//$conf['cache_backends'][] = 'sites/all/modules/contrib/redis/redis.autoload.inc';
//$conf['redis_client_interface'] = 'PhpRedis'; // Can be "Predis".
//$conf['redis_client_host'] ='redis';  // Your Redis instance hostname.
//$conf['cache_default_class'] = 'Redis_Cache';
//$conf['cache_class_cache_form'] = 'DrupalDatabaseCache';
//$conf['cache_prefix'] = '';


//$conf['stage_file_proxy_hotlink'] = 0;
//$conf['stage_file_proxy_use_imagecache_root'] = 1;
//$conf['stage_file_proxy_origin_dir'] = 'sites/default/files';
//$conf['stage_file_proxy_origin'] = 'http://example-file-source-site.com';

$conf['theme_debug'] = TRUE;
$conf['file_temporary_path'] = '/tmp';
$base_url = "http://docker.dev";
