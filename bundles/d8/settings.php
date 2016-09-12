<?php

$settings['hash_salt'] = '9xR-J3xdXrNcQQtwV-dP-AVqsom5jc1SNgCYaGkNqU-l0KqihpsDY8-bK70PbqhpTM9B3mRaUg';
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = __DIR__ . '/services.yml';

if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}
