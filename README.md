![DockerDrupal Logo](https://raw.githubusercontent.com/4alldigital/DockerDrupal/master/docs/images/dd-logo.png)

[DockerDrupal](https://www.4alldigital.io/docker-drupal) is Docker based development environment for local Drupal websites, Wordpress websites or PHP apps. Useful for debugging and developing your projects, with a possible intention of hosting sites using [DockerDrupal Prod](https://github.com/4alldigital/drupalprod-docker) (A read-only production environment).

# DockerDrupal-Cli - BETA
### CLI utility for DockerDrupal

### Questions?
  Ping me on [Twitter](http://twitter.com/@4alldigital)

```composer global require dockerdrupal/cli```

# Status
## Initial Commands structure
```
     --------------
     $ dockerdrupal build:destroy   [destroy] Disable and delete APP and containers
     $ dockerdrupal build:init      [init] Fetch and build DockerDrupal containers docker
     --------------
     $ dockerdrupal docker:restart  [restart] Restart APP containers
     $ dockerdrupal docker:start    [start] Start APP containers
     $ dockerdrupal docker:stop     [stop] Stop all containers
     --------------
```

## Example Commands
```
      --------------
      dockerdrupal exec -s [service/container name] -c "[command to execute against container service]"

      dockerdrupal exec -s php -c "drush cc all"

      dockerdrupal exec -s nginx -c "nginx -s reload"

      OR

      dockerdrupal exec [follow prompts]

```

# Next step :

 - D7 app build


For more information see [roadmap](https://github.com/4AllDigital/DockerDrupalCli/blob/master/roadmap.md)

# Known issues

1. Its not finished
