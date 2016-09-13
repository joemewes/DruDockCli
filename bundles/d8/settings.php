<?php

$settings['hash_salt'] = 'oPiG1n7kFE1rz0QkcjuqYnxEpLd_O9EE9I2gyfplLpuRGsuECG1HJr5MQ0z1zlbTCln53cIbsw';
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = __DIR__ . '/services.yml';

if (file_exists('/app/shared/settings.local.php')) {
    include '/app/shared/settings.local.php';
}
