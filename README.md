![DockerDrupal Logo](https://raw.githubusercontent.com/4alldigital/DockerDrupal/master/docs/images/dd-logo.png)

[DockerDrupal](https://www.4alldigital.io/docker-drupal) is Docker based development environment for local Drupal websites, Wordpress websites or PHP apps. Useful for debugging and developing your projects, with a possible intention of hosting sites using [DockerDrupal Prod](https://github.com/4alldigital/drupalprod-docker) (A read-only production environment).

# DockerDrupal-Cli - BETA
### CLI utility for DockerDrupal

  START by installing & launching the Docker for Mac Application 
   - https://download.docker.com/mac/stable/Docker.dmg

### Questions?
  Ping me on [Twitter](http://twitter.com/@4alldigital)
  
### Example experience
   - video 1 (Installation and setup) - https://www.youtube.com/watch?v=XfmN47xdrgY
   - video 2 (Initialise Drupal7 Env && App) - https://www.youtube.com/watch?v=FoDeyEPEhiY
   
### Install via Composer
  - Install Composer [Globally](https://getcomposer.org/doc/00-intro.md#globally) 
  - Make sure you add composers vendor directory to your $PATH:
    - add `export PATH="~/.composer/vendor/bin:$PATH"` to your ~/.bash_profile
  - Install DockerDrupal globally.

```
composer global require dockerdrupal/cli
```

- Note : drupal/console and drush/drush have complex dependencies and may cause conflicts with this utility. You can resolve this by adding the following to your global/project composer.json file:

```
{
    "require": {
        "dockerdrupal/cli": "1.0.9"
    }
}
```


# Status
## Initial Commands structure
```
     build
       build:destroy      Disable and delete APP and containers
       build:init         Fetch and build Drupal apps
      docker
       docker:exec        Execute bespoke commands at :container
       docker:logs        Montitor logs output of container
       docker:restart     Restart APP containers
       docker:start       Start APP containers
       docker:status      Get current status of all containers
       docker:stop        Stop all containers
      drush
       drush:cmd          Run drush commands
      env
       env:init           Fetch and build DockerDrupal containers
      redis
       redis:flush        FLush Redis cache
       redis:info         Get Redis running config information
       redis:monitor      Montitor redis activity
       redis:ping         Ping Redis
      self
       self:about         About DockerDrupal
      util
       util:mysql:export  Export .sql files [utilme]
       util:mysql:import  Import .sql files [utilmi]
```

## Example Commands
```
      --------------
      $ dockerdrupal env my-great-app -t DEFAULT
      $ dockerdrupal env my-drupal8-site -t D8
      $ dockerdrupal env my-drupal7-site -t D7
      --------------

      $ dockerdrupal build:init

      --------------

```

# Next step :

 - More utility commands and USER feedback

For more information see [roadmap](https://github.com/4AllDigital/DockerDrupalCli/blob/master/roadmap.md)

# Known issues

1. Its not finished
