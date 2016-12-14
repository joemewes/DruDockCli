![DockerDrupal Logo](https://raw.githubusercontent.com/4alldigital/drupaldev-docker/master/docs/images/dd-logo.png)

[DockerDrupal](https://www.4alldigital.io/docker-drupal) is Docker based development environment for local Drupal websites, Wordpress websites or PHP apps. Useful for debugging and developing your projects, with a possible intention of hosting sites using [DockerDrupal Prod](https://github.com/4alldigital/drupalprod-docker) (A read-only production environment).

<p align='left'>
[![Drupal version](https://img.shields.io/badge/Drupal-8-blue.svg)]()
[![Drupal version](https://img.shields.io/badge/Drupal-7-green.svg)]()
[![Docker version](https://img.shields.io/badge/Docker-12.0-blue.svg)]()
<br clear='all'/>

------------------------------------------------------------------------------------------------

### Questions?
 Ping me on [Twitter](http://twitter.com/@4alldigital)

------------------------------------------------------------------------------------------------

### - CLI coming soon see - https://github.com/4AllDigital/DockerDrupalCli
 + config & structural updates simplification see - https://github.com/4AllDigital/DockerDrupal-lite
 + significant performacne gains using UNISON see - https://github.com/docker/for-mac/issues/77#issuecomment-247972981
  

------------------------------------------------------------------------------------------------

  ### PreRequisites
  1. Git
  2. Basic understanding of bash/command-line


  ### Set up Docker Environment
  1. Install and run [Docker for Mac](https://docs.docker.com/docker-for-mac)
  2. In terminal paste and run the following:

  ```
   mkdir -p ~/infra && \
   cd ~/infra && \
   git clone https://github.com/4alldigital/drupaldev-docker.git && \
   cd ~/infra/drupaldev-docker && \
   caffeinate -i time ./scripts/onboardme.sh

  ```


  ### Setup Basic Drupal 7 site
  1. Open `Terminal.app` application in your /Applications/Utilities/ folder
  2. From the command-line run the following:

  ```
     cd ~/infra/drupaldev-docker && \
     ./scripts/initdrupal.sh

  ```

  At the end of the `initdrupal` script, 4 browser tabs should be open, with mailcatcher, SOLR, Selenium Grid and a demo Drupal install running.  First off, we'd check out the way the demo Drupal site is set up, and try to reproduce your own.

# What next?

DockerDrupal currently utilise the following containers:

 1. https://hub.docker.com/r/4alldigital/drupaldev-php7

 2. https://hub.docker.com/r/4alldigital/drupaldev-redis

 3. https://hub.docker.com/r/4alldigital/drupaldev-behat

 4. https://hub.docker.com/r/4alldigital/drupaldev-nginx

 5. https://hub.docker.com/r/4alldigital/drupaldev-solr

 6. https://hub.docker.com/r/selenium/hub

 7. https://hub.docker.com/r/selenium/node-chrome-debug

 8. https://hub.docker.com/r/selenium/node-firefox-debug

 9. https://hub.docker.com/r/schickling/mailcatcher

 10. https://hub.docker.com/r/_/mariadb

 11. https://hub.docker.com/r/jwilder/docker-gen

 12. https://hub.docker.com/r/jwilder/nginx-proxy


# Read docs

Our work-in-progress documentation will live on readthedocs.org from now on. Visit http://dockerdrupal.readthedocs.org/en/latest
