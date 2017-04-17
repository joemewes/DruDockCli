<?php

/**
 * @file
 * Contains \Docker\Drupal\Extension\DemoCommand.
 */

namespace Docker\Drupal\Extension;

/**
 * Class DatabaseConfigExtension
 * @package Docker\Drupal\Extension
 */

//use Symfony\Component\Yaml\Parser;

class DatabaseConfigExtension extends Extension
{
    public function load( array $configs, ContainerBuilder $container )
    {
    $configuration = new Configuration();
    $processedConfig = $this->processConfiguration( $configuration, $configs );

    // Do not add a paramater now, just continue reading the services.
    $loader = new YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
    $loader->load( '../config/database.yml' );

    }
}