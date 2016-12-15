# DockerDrupalCli

## build ENV- docker-compose.yml
- Dynamic build of docker-compose.yml via Q & A rather than from alternative GIT repo

- [X] DEFAULT
- [x] WITH TESTING SUITE
- [ ] CUSTOM
- [ ] READ-ONLY PROD

1. -> DEFAULT - [DockerDrupal-lite](https://github.com/4AllDigital/DockerDrupal-lite)  
2. -> WITH TESTING SUITE - [DockerDrupal](https://github.com/4AllDigital/DockerDrupal)

APPROACH:

    - initial :env command give app requirements Q
        -> for now : dd/dd-lite option (later add /custom)
    - specify appy req's choices in config.yml for use later
    - download DD from git repo and build env
    - NEW - add DD main commands
    
' THOUGHTS: should move dd-lite and dd into /CLI repo ?? '

@2017
3. -> CUSTOM - Q & A

    - Mysql [y/n]
    - PHP5.6 [y/n]
    - PHP7 [y/n]
    - NGINX [y/n]
    - APACHE [y/n]
    - mySQL DB [y/n]
    - Postgres DB [y/n]
    - Mongo DB [y/n]
    - Redis [y/n]
    - Memcached [y/n]
        
- Each option will need to: 
1. Print out required config in .yml file including  /Volumes for DBs
2. Copy/create relevant .env files
3. Copy/create relevant /config files

## remote control
After merging and update of READ_ONLY prod environment:

- [ ] ADD/GENERATE remote tools -> SSH keys/Drush alias'
- [ ] Interact with remote /CLI - drush && docker && ??
 

## General continued things it must do :                                                                                    
- init app/dockerdrupal ./config.yaml on manually created app/old version
- the use of Port :80 will prevent multiple apps/services running concurrently
- with services running
    - linux -> write 127.0.0.1 docker.dev to /etc/hosts file
    - Windows -> ??     
       
## Active APP commands @TODO :
DRUSH

- drush:features:revert \[all] (drush:fr)
- drush:features:update \[all] (drush:fu)

UTIL

- SUPRESS VERSION WARNING COMMAND - ADD TO CONFIG AND READ PRE-WARNING FROM CONFIG
- \<CONTAINER\> bash
- multisite drush -> args :multi
- Interact with mailcatcher
    - flush
    - debug ??

GENERAL

- python -mwebbrowser http://docker.dev:8983/solr/#/SITE
- python -mwebbrowser http://docker.dev:4444/grid/console
- python -mwebbrowser http://docker.dev:1080
