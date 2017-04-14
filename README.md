![DruDock Logo](https://raw.githubusercontent.com/4alldigital/DruDock/master/docs/images/dd-logo.png)

[DruDock](https://www.4alldigital.io/drudock) is Docker based development, staging and production environment for Drupal websites or PHP apps.

[![Latest Stable Version](https://poser.pugx.org/drudock/cli/v/stable)](https://packagist.org/packages/drudock/cli)
[![Build Status](https://travis-ci.org/4AllDigital/DruDockCli.svg?branch=master)](https://travis-ci.org/4AllDigital/DruDockCli)
[![License](https://poser.pugx.org/drudock/cli/license)](https://github.com/4AllDigital/DruDockCli/blob/master/LICENSE.txt)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/DruDockCli/Lobby?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)


# DruDock-Cli - BETA
### CLI utility for DruDock

Install and setup Docker
  
- Mac : https://www.docker.com/docker-mac : 
[_download_](https://store.docker.com/editions/community/docker-ce-desktop-mac)
- Windows : https://www.docker.com/docker-windows
[_download_](https://store.docker.com/editions/community/docker-ce-desktop-windows)
- Linux
  - Ubuntu : https://www.docker.com/docker-ubuntu
  [_download_](https://store.docker.com/editions/community/docker-ce-server-ubuntu)
  - Centos : https://www.docker.com/docker-centos-distribution
  [_download_](https://store.docker.com/editions/community/docker-ce-server-centos)
   
### Minimum Requirements : 
- Host OS must have PHP ^5.6

### Questions?
  Ping me on [Twitter](http://twitter.com/@4alldigital)
  
### DOCS - @TODO
  Read more - http://drudockcli.readthedocs.io/en/latest/
  
### Example experience
   - video 1 (Installation and setup) - https://www.youtube.com/watch?v=XfmN47xdrgY
   - video 2 (Initialise Drupal7 Env && App) - https://www.youtube.com/watch?v=FoDeyEPEhiY
   
### Install via Composer
  - Install Composer [Globally](https://getcomposer.org/doc/00-intro.md#globally) 
  - Make sure you add composers vendor directory to your $PATH:
    - add `export PATH="~/.composer/vendor/bin:$PATH"` to your ~/.bash_profile
  - Install DruDock globally.

```
composer global require drudock/cli
```

- Note : drupal/console and drush/drush have complex dependencies and may cause conflicts with this utility. You should be able to resolve this by adding the following to your global/project composer.json file, and then running 'composer global update' :

```
{
    "require": {
        "drudock/cli": "@stable"
    }
}
```


# Status
## Initial Commands structure
```
     Available commands:
       about                     About DruDock
       destroy                   Disable and delete APP and containers
       env                       Fetch and build DruDock containers
       exec                      Execute bespoke commands at :container
       help                      Displays help for a command
       init                      Fetch and build Drupal apps
       list                      Lists commands
       restart                   Restart current APP containers
       start                     Start current APP containers
       status                    Get current status of all containers
       stop                      Stop current APP containers
      behat
       behat:cmd                 Run behat commands
       behat:monitor             Launch behat VNC viewer
       behat:status              Runs example command against running APP and current config
      build
       build:destroy             Disable and delete APP and containers
       build:init                Fetch and build Drupal apps
      docker
       docker:exec               Execute bespoke commands at :container
       docker:restart            Restart current APP containers
       docker:start              Start current APP containers
       docker:status             Get current status of all containers
       docker:stop               Stop current APP containers
       docker:update:config      Update APP config
       docker:update:containers  Update APP containers
      drush
       drush:cc                  Run drush cache clear 
       drush:cmd                 Run drush commands 
       drush:dis                 Disable/Uninstall Drupal module
       drush:en                  Enable Drupal module
       drush:init:config         Run drush config init
       drush:uli                 Run Drush ULI
       drush:updb                Run Drush updb
      env
       env:init                  Fetch and build DruDock containers
      mysql
       mysql:export              Export .sql files
       mysql:import              Import .sql files
       mysql:log                 Monitor mysql activity
      nginx
       nginx:addhost             Add nginx host to DD and host OS
       nginx:flush               Flush nginx cache
       nginx:log                 Monitor nginx activity
       nginx:reload              Reload nginx activity
      prod
       prod:update               Rebuild app and deploy latest code into app containers
      redis
       redis:flush               Flush Redis cache
       redis:info                Get Redis running config information
       redis:monitor             Montitor redis activity
       redis:ping                Ping Redis
      self
       self:about                About DruDock
      sync
       sync:monitor              Montitor current App sync activity
      up
       up:cg                     Update APP config
       up:ct                     Update APP containers

       
```

## Example Commands
```
      --------------
      :$ drudock env my-great-app -t DEFAULT -r Basic -s New -p basic.docker.dev
      :$ cd my-great-app && drudock build:init
      --------------
```    
#### DEV Drupal 8  
```
      --------------
      :$ drudock env my-drupal8-site -t D8 -r Basic -s New -p d8.docker.dev
      :$ cd my-drupal8-site && drudock build:init
      --------------
```   
#### DEV Drupal 7
```
      --------------
      :$ drudock env my-drupal7-site -t D7 -r Full -s Git -p d7.docker.dev
      :$ cd my-drupal7-site && drudock build:init
      --------------
```

### Next step :

 - More utility commands and USER feedback
For more information see [roadmap](https://github.com/4AllDigital/DruDockCli/blob/master/roadmap.md)

### Known issues

1. Its not finished - still in early -alpha and requires community testing and feedback.
