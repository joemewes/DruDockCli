![DockerDrupal Logo](https://github.com/4alldigital/DockerDrupal-lite/raw/master/docs/images/ddlite-logo.png)

[DockerDrupal lite](https://www.4alldigital.io/docker-drupal) is Docker based lite development environment for local Drupal websites, Wordpress websites or PHP apps. Useful for debugging and developing your projects, with a possible intention of hosting sites using [DockerDrupal Prod](https://github.com/4alldigital/drupalprod-docker) (A read-only production environment).

<p align='left'>
[![Drupal version](https://img.shields.io/badge/Drupal-8-blue.svg)]()
[![Drupal version](https://img.shields.io/badge/Drupal-7-green.svg)]()
[![Docker version](https://img.shields.io/badge/Docker-12.0-blue.svg)]()
<br clear='all'/>


### important !!

should be used via : https://github.com/4AllDigital/DockerDrupalCli to avoid temporary app-sync issue causing php OCI start error.

------------------------------------------------------------------------------------------------

### Questions?
 Ping me on [Twitter](http://twitter.com/@4alldigital)

------------------------------------------------------------------------------------------------

### - CLI coming soon
  https://github.com/4AllDigital/DockerDrupalCli

------------------------------------------------------------------------------------------------


  ### PreRequisites
   -  Git
   - Basic understanding of bash/command-line

  ### Set up Docker Environment
  1. Install and run [Docker for Mac](https://docs.docker.com/docker-for-mac)
  2. ADD HOST IP var to enviromemnt : ```export DOCKER_HOST_IP=$(ipconfig getifaddr en0)```
  3. git clone https://github.com/4alldigital/DockerDrupal-lite.git docker_\<your-app-name>
  4. cd docker_\<your-app-name>
  5. docker-compose up -d

   - DD-lite must live next to you /app folder

```
      .
      ├── app
      │   └── www
      ├── docker_<yourappname>
```




## USEFUL COMMANDS

    1. docker-compose pull (pull images | updates)
    2. docker-compose up -d (start app containers and daemon to run in the background)
    3. docker-compose stop (stop app containers)
    4. docker exec -i <ddl>_nginx_1 tail -f /var/log/nginx/app-error.log (tail nginx log)
    5. docker exec -i <ddl>_php_1 drush cc all (clear Drupal cache)
    6. docker logs -f <ddl>_app_1 (follow app sync logs and processes)


# What next?

DockerDrupal currently utilise the following containers:

 1. https://hub.docker.com/r/4alldigital/drupaldev-php7

 2. https://hub.docker.com/r/4alldigital/drupaldev-nginx

 3. https://hub.docker.com/r/_/mariadb
