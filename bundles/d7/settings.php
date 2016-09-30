<?php

$update_free_access = FALSE;
$drupal_hash_salt = '5vNH-JwuKOSlgzbJCL3FbXvNQNfd8Bz26SiadpFx6gE';
// @todo - find out why this doesnt work in containers re:symlinks [is : /app/repository/settings.local.php] [should be: /app/builds/.../settings.local.php]
//$local_settings = dirname(__FILE__) . '/settings.local.php';

if (file_exists('/app/shared/settings.local.php')) {
    include '/app/shared/settings.local.php';
}
