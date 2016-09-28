# DockerDrupalCli

## Things it must do
```
######################################################################################
#                                                                                    #
# - check requirements                                                               #
#    - Docker for Mac/Windows/Linux                                                  #
#        - docker Daemon running                                                     #
#    - PHP version                                                                   #
#    - Drush version ` shell_exec('which drush') `                                   #
#    - GIT installed ` shell_exec('which git') `                                     #
#    - WRITE .yaml file with APP .config INFO to reading later. eg. type: [D8]       #
#                                                                                    #
######################################################################################

```

- GET DockerDrupal and setup basic index.php/html welcome page
  -> full
    - git clone https://github.com/4alldigital/DockerDrupal.git docker_<appname>
  -> lite
    - git clone https://github.com/4alldigital/DockerDrupal-lite.git docker_<appname>

    ```
          .        
          ├── app
          |   ├── index.html
          └── docker_<yourappname>  
              ├── config
              │   ├── mysql
              │   └── solr
              ├── docs
              │   └── images
              ├── scripts
              └── sites-enabled
                  └── docker.dev
    ```

- Init DockerDrupal
    - scaffold app/docker directory structure
      - http://symfony.com/doc/current/bundles/best_practices.html
      - http://symfony.com/doc/current/bundles.html
    - ASK
        : Drupal [default] [version]
        : simple [/app/www/index.php] -> hello world

        - :build DockerDrupal lite? full?        
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
    - get container name
    - STOP ALL running containers
    - \<CONTAINER\> bash
    - Monitor APP sync
    - drush ULI
    - drush clear cache
    - multisite drush -> args :multi
    - redis clearcache
    - open mailcatcher
    - launch ??
    - mysql log
    - Nginx log :error
    - Nginx RELOAD
    - backup/export Database -> integration with AWS cli ??
    - restore/import database : local or remote source ??


- Other @TODO CLI -≥ commands
   python -mwebbrowser http://localhost:8983/solr/#/SITE
   python -mwebbrowser http://localhost:4444/grid/console
   python -mwebbrowser http://localhost:1080
   python -mwebbrowser http://localhost:8088

