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
  
### Example experience
   - video 1 (Installation and setup) - https://www.youtube.com/watch?v=XfmN47xdrgY
   - video 2 (Initialise Drupal7 Env && App) - https://www.youtube.com/watch?v=FoDeyEPEhiY
   
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
       app:exec               [exec] Execute bespoke commands at :container
       app:init:containers    [init:ct] Create APP containers
       app:open               [open] Open APP in default browser.
       app:restart            [restart] Restart current APP containers
       app:start              [start] Start current APP containers
       app:status             [status] Get current status of all containers
       app:stop               [stop] Stop current APP containers
       app:update:config      [up:cg] Update APP config
       app:update:containers  [up:ct] Update APP containers
      behat
       behat:cmd              Run behat commands
       behat:monitor          Launch behat VNC viewer
       behat:status           Runs example command against running APP and current config
      build
       build:destroy          [destroy] Disable and delete APP and containers
       build:init             [init] Fetch and build Drupal apps
      drush
       drush:cc               Run drush cache clear 
       drush:cmd              Run drush commands 
       drush:dis              Disable/Uninstall Drupal module
       drush:en               Enable Drupal module
       drush:init:config      Run drush config init
       drush:uli              Run Drush ULI
       drush:updb             Run Drush updb
      env
       env:init               Fetch and build DruDock containers
      init
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
      up
       up:cg                     Update APP config
       up:ct                     Update APP containers

       
```

## Example Commands
```
      --------------
      :$ drudock env:init defaultapp --type DEFAULT --dist Development --src New --apphost drudock.localhost --services "PHP,NGINX,MYSQL"
      :$ cd my-great-app && drudock build:init
      --------------
```    
#### DEV Drupal 8  
```
      --------------
      :$ drudock env:init defaultapp --type D8 --dist Development --src New --apphost drudock.localhost --services "PHP,NGINX,MYSQL"
      :$ cd my-drupal8-site && drudock build:init
      --------------
```   
#### DEV Drupal 7
```
      --------------
      :$ drudock env:init defaultapp --type D7 --dist Development --src New --apphost drudock.localhost --services "PHP,NGINX,MYSQL"
      :$ cd my-drupal7-site && drudock build:init
      --------------
```

### Next step :

 - More utility commands and USER feedback
For more information see [roadmap](https://github.com/4AllDigital/DruDockCli/blob/master/roadmap.md)

### Known issues

1. Its not finished - still in alpha and requires community testing and feedback.
