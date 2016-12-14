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

$aliases['dockerdrupal'] = array (
  'uri' => 'http://default',
  'root' => '/app/www',
  'remote-host' => 'docker.dev',
  'remote-user' => 'root',
  'ssh-options' => '-p 8022', // optional if required
);
