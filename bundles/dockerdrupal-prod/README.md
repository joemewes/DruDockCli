![Drupal Docker Logo](https://raw.githubusercontent.com/4alldigital/drupaldev-docker/master/docs/images/drupal-docker-logo-monochrome.png)

[DockerDrupal Prod](http://www.4alldigital.io/docker-drupal) is Docker based, read-only production environment for hosting Drupal websites.

### Inside the box

## Docker-Compose
We use docker-compose to setup local networks, volumes and container and manage our development environment.
Visit [Docker Compose V1.7rc-1](https://docs.docker.com/compose/) For more info...

## MariaDB
MariaDB is one of the most popular database servers in the world. It’s made by the original developers of MySQL and guaranteed to stay open source.

Visit [MariaDB](https://mariadb.org) For more info...

## NGINX
NGINX is a free, open-source, high-performance HTTP server and reverse proxy, as well as an IMAP/POP3 proxy server.

Visit [Nginx](https://www.nginx.com/resources/wiki/) For more info...

### Configuration:
  - on Linux you can the install paths of your app on the host plus a specific directory path for fule uploads from your CMS.
  eg:
   - export APPS_PATH=/app
   - export LOCAL_FILESPATH=/app/sites/default/files
   - export DOCKER_FILESPATH=/docker/sites/default/files

## PHP-FPM
PHP-FPM (FastCGI Process Manager) is an alternative PHP FastCGI implementation with some additional features useful for sites of any size, especially busier sites.

Visit [PHP-FPM](http://php-fpm.org) For more info...

## APACHE SOLR
Solr is the popular, blazing-fast, open source enterprise search platform built on Apache Lucene™.

Visit [APACHE SOLR V4.10.1](http://lucene.apache.org/solr/) For more info...

### Configuration:
 - To add a new index:
 1. Copy the [site] folder in /mounts/conf/solr/ adn rename eg. sitetwo
 2. Remove the /data directory
 3. Name you index by editing core.properties in your new folder
 4. Copy example-realm.properties to realm.properties and set the password to something unique

## REDIS
Redis is an open source (BSD licensed), in-memory data structure store, used as database, cache and message broker.

Visit [REDIS](http://redis.io) For more info...

### License

This project is licensed under the MIT open source license.

### Why?!....

We developed this becasue we love Docker and Drupal and think micro-service infrastructure is the way forward....  We hope you enjoy all or parts of the stack and value any feedback or contributions.
