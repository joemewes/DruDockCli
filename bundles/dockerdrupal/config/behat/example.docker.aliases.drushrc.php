<?php

/*
 * Example alias' format for docker host/container -> docker container
 */

// $aliases['your_site'] = array (
//   'uri' => 'default',
//   'root' => '/path/to/website/',
//   'remote-host' => 'localhost.dev',
//   'remote-user' => 'root',
//   'ssh-options' => '-p 8022', // optional if required
// );

$aliases['drupal_docker'] = array (
  'uri' => 'http://default',
  'root' => '/docker/drupal_docker',
  'remote-host' => 'drupal.docker',
  'remote-user' => 'root',
  'ssh-options' => '-p 8022', // optional if required
);
