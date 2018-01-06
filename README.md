![DruDock Logo](https://s3.eu-west-2.amazonaws.com/drudock/DruDockLogo.jpg)

[DruDock](https://www.4alldigital.io/drudock) is Docker based development, staging and production environment for Drupal websites or PHP apps.

[![Latest Stable Version](https://poser.pugx.org/drudock/cli/v/stable)](https://packagist.org/packages/drudock/cli)
[![License](https://poser.pugx.org/drudock/cli/license)](https://packagist.org/packages/drudock/cli)
[![composer.lock](https://poser.pugx.org/drudock/cli/composerlock)](https://packagist.org/packages/drudock/cli)

[![Build Status](https://travis-ci.org/4AllDigital/DruDockCli.svg?branch=master)](https://travis-ci.org/4AllDigital/DruDockCli)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/DruDockCli/Lobby?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)


# DruDock-Cli - BETA
### CLI utility for DruDock

Install and setup Docker
  
- Mac : https://www.docker.com/docker-mac : 
[_download_](https://store.docker.com/editions/community/docker-ce-desktop-mac)
  - NB: Currently limited to EDGE version for latest :cached performance gains. (https://download.docker.com/mac/edge/Docker.dmg)
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
   
### Install via .phar
  - Install DruDock globally.
  
  ``` 
  
  curl -O http://d1gem705zq3obi.cloudfront.net/drudock.phar && \
  mv drudock.phar /usr/local/bin/drudock && \
  chmod +x /usr/local/bin/drudock && \
  drudock
  
  ```

# Status
## Initial Commands structure
```
  Available commands:
    help                   Displays help for a command
    list                   Lists commands
   app
    app:build              [ab] Fetch and build App containers and resources.
    app:destroy            [ad] Disable and delete APP and containers
    app:exec               [ae] Execute bespoke commands at :container
    app:init               [ai] Fetch and build DruDock containers
    app:init:build         [aib] Initialize environment and run build.
    app:init:containers    [aic] Create APP containers
    app:open               [ao] Open APP in default browser.
    app:restart            [ar] Restart current APP containers
    app:start              [start] Start current APP containers
    app:status             [as] Get current status of all containers
    app:stop               [stop] Stop current APP containers
    app:update:config      [aucg] Update APP config
    app:update:containers  [auct] Update APP containers
   behat
    behat:cmd              Run behat commands
    behat:monitor          Launch behat VNC viewer
    behat:status           Runs example command against running APP and current config
   drush
    drush:cc               [dcc] Run drush cache clear
    drush:cmd              [dc] Run drush commands
    drush:dis              [dd] Disable/Uninstall Drupal module
    drush:en               [de] Enable Drupal module
    drush:init:config      [dicg] Run drush config init
    drush:rr               [drr] Run drush registry rebuild
    drush:uli              [duli] Run Drush ULI
    drush:updb             [dudb] Run Drush updb
   mysql
    mysql:export           Export .sql files
    mysql:import           Import .sql files
    mysql:log              Monitor mysql activity
   nginx
    nginx:flush            Flush nginx cache
    nginx:log              Monitor nginx activity
    nginx:proxy:start      Start nginx proxy
    nginx:proxy:stop       Stop nginx proxy
    nginx:reload           Reload nginx activity
    nginx:sethost          Add nginx host to DD and host OS
   prod
    prod:update            Rebuild app and deploy latest code into app containers
   redis
    redis:flush            Flush Redis cache
    redis:info             Get Redis running config information
    redis:monitor          Montitor redis activity
    redis:ping             Ping Redis
   self
    self:about             [about] About DruDock

       
```

## Example Commands
```
      --------------
      :$ drudock app:init --appname defaultapp --type DEFAULT --dist Development --src New --apphost drudock.localhost --services "PHP,NGINX,MYSQL"
      :$ cd my-great-app && drudock build:init
      --------------
```    
#### DEV Drupal 8  
```
      --------------
      :$ drudock app:init:build --appname my-app --type D8 --dist Development --src New --apphost drudock.localhost --services "PHP,NGINX,MYSQL"
      :$ cd my-drupal8-site && drudock build:init
      --------------
```   
#### DEV Drupal 7
```
      --------------
      :$ drudock app:init:build --appname my-app --type D7 --dist Development --src New --apphost drudock.localhost --services "PHP,NGINX,MYSQL"
      :$ cd my-drupal7-site && drudock build:init
      --------------
```

### Next step :

 - More utility commands and USER feedback
For more information see [roadmap](https://github.com/4AllDigital/DruDockCli/blob/master/roadmap.md)

### Known issues

1. Its not finished - still in alpha and requires community testing and feedback.
