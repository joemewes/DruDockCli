# DockerDrupalCli

## Things it must do
- check requirements    
    - Docker for Mac/Windows/Linux
        - docker Daemon running
    - PHP version
    - Drush version ` shell_exec('which drush') `
    - GIT installed ` shell_exec('which git') `

- Init DockerDrupal
    - scaffold app/docker directory structure
    - ASK
        : Drupal [default] [version]
        : simple [/app/www/index.php] -> hello world

        - :build DockerDrupal lite? full?
        -> full
          - git clone https://github.com/4alldigital/DockerDrupal.git docker_<appname>
        -> lite
          - git clone https://github.com/4alldigital/DockerDrupal-lite.git docker_<appname>
        - setup repo/app files eg
        ```
              .        
              ├── app
              |   ├── builds
                  │   ├── build-2016-08-08--09-30-00
                  │   │   └── public/index.php
                  ├── repository
                  │   ├── libraries/
                  │   ├── modules
                  │   │   ├── custom/
                  │   │   └── features/
                  │   ├── scripts
                  │   └── themes
                  │       └── custom/
                  ├── shared
                  │   └── files/
                  └── www -> builds/build-2016-08-08--09-30-00/public
              └── docker_<yourappname>  
                  ├── config
                  │   ├── mysql
                  │   └── solr
                  ├── docs
                  │   └── images
                  ├── scripts
                  └── sites-enabled
        ```
    - run app build
            - Download Drupal and symlink custom folders
    - cd docker_<yourappname>
    - check for currently running dockerdrupal apps/containers
        - the use of Port :80 will prevent multiple apps/services running concurrently
    - docker-compose up -d
            - this will create networks, volumes and containers
            - @todo - fix PHP7 working directory compile vs sync issue
    - with services running
        - Install Drupal site
        - OSX -> write 127.0.0.1 docker.dev to /etc/hosts file
        - linux -> write 127.0.0.1 docker.dev to /etc/hosts file
        - Windows -> ??        
        - Open http://docker.dev in browser

    - be able to import local DB / .sql dump
    - build multisite? add site?

- Other @TODO CLI -≥ commands
   python -mwebbrowser http://localhost:8983/solr/#/SITE
   python -mwebbrowser http://localhost:4444/grid/console
   python -mwebbrowser http://localhost:1080
   python -mwebbrowser http://localhost:8088

   # get container name
   docker ps --format {{.Names}} | grep php

   # \<CONTAINER\> bash
   docker exec -i $(docker ps --format {{.Names}} | grep php) bash

   # drush ULI
   docker exec -i $(docker ps --format {{.Names}} | grep php) drush uli 1

   # redis clearcache
   docker exec -i $(docker ps --format {{.Names}} | grep redis) redis-cli flushall

   # open mailcatcher
   python -mwebbrowser http://localhost:1080

   # launch ??
   python -mwebbrowser http://docker.dev

   # multisite drush -> args :multi
   docker exec -i $(docker ps --format {{.Names}} | grep php) drush -l http://docker.dev uli admin
