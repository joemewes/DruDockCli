#!/bin/bash

BLUE='\033[0;34m'
GREEN='\033[0;32m'
NC='\033[0m'

readonly THEMENAME=dockerdrupal

cat <<EOF

   _  _        _    _ _    ____  _       _ _        _
  | || |      / \  | | |  |  _ \(_) __ _(_) |_ __ _| |
  | || |_    / _ \ | | |  | | | | |/ _` | | __/ _` | |
  |__   _|  / ___ \| | |  | |_| | | (_| | | || (_| | |
     |_|   /_/   \_\_|_|  |____/|_|\__, |_|\__\__,_|_|
                                   |___/

EOF

# move up to repo base folder
cd ../

# create folders
echo -e "${BLUE}BUILDING DIRECTORY STRUCTURE${NC}"

  # builds folder
  mkdir -p ../builds
  # shared folder
  mkdir -p ../shared
  # files folder
  mkdir -p ../shared/files

# add files
file=../shared/settings.local.php

if ! [ ! -e "$file" ]
then
  echo "local.settings file already exists."
else
  if ! echo "<?php
    // Database configuration.
    \$databases['default']['default'] = array(
      'driver' => 'mysql',
      'host' => 'db',
      'username' => 'root',
      'password' => 'password',
      'database' => '',
      'prefix' => '',
    );" > ../shared/settings.local.php
  then
            echo "ERROR: the virtual host could not be added."
  else
            echo "New virtual host added to the Apache vhosts file"
  fi
  echo "local.settings file added"
fi

#build latest drupal filders and folders from git update

now="$(date +'%Y-%m-%d--%H-%M-%S')"

echo -e "${BLUE}BUILD DRUPAL VIA MAKE FILE${NC}"

cd ../
drush make repository/project.make.yml builds/build-$now/public

echo -e "${BLUE}SYMLINK NEW DIRECTORIES${NC}"
ln -s ../../../../../repository/themes builds/build-$now/public/sites/default/themes
ln -s ../../../../../repository/modules builds/build-$now/public/sites/default/modules
ln -s ../../../../../repository/libraries builds/build-$now/public/sites/default/libraries
ln -s ../../../../../shared/settings.local.php builds/build-$now/public/sites/default/settings.local.php
ln -s ../../../../../shared/files builds/build-$now/public/sites/default/files

settingsfile=builds/build-$now/public/sites/default/settings.php

if ! [ ! -e "$settingsfile" ]
then
  echo "settings file already exists."
else
  if ! echo "<?php
    \$update_free_access = FALSE;
    \$drupal_hash_salt = '5vNH-JwuKOSlgzbJCL3FbXvNQNfd8Bz26SiadpFx6gE';
    \$local_settings = dirname(__FILE__) . '/settings.local.php';
    if (file_exists(\$local_settings)) {
      require_once(\$local_settings);
    }" > builds/build-$now/public/sites/default/settings.php
  then
            echo "ERROR: the virtual host could not be added."
  else
            echo "New virtual host added to the Apache vhosts file"
  fi
  echo "local.settings file added"
fi

echo -e "${BLUE}SYMLINK NEW WWW -> BUILD${NC}"
rm www
ln -s builds/build-$now/public www

echo -e "${BLUE}COUNT BUILDS${NC}"
buildscount=$(ls -l builds | grep ^d | wc -l)

echo -e "${BLUE}OLDEST DIR${NC}"
oldest=$(ls -t1 builds | tail -n 1)

if [ $buildscount -gt 5 ]
then
  echo -e "${BLUE}Remove oldest build${NC}"
  rm -R builds/$oldest
fi

echo -e "${BLUE}RUN DRUSH COMMANDS FROM HOST${NC}"

cd www/sites/all/themes/
mkdir custom
git clone git@github.com:4alldigital/4ad-basetheme.git base_alldigital

cd base_alldigital/
npm install
bower install
gulp sass:prod

cd ../../../default/themes/custom/$THEMENAME

npm install
bower install
gulp sass:prod

