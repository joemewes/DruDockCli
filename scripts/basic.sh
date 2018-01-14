#!/bin/bash

BLUE='\033[0;34m'
GREEN='\033[0;32m'
NC='\033[0m'

cat <<EOF

  _  _        _    _ _    ____  _       _ _        _
 | || |      / \  | | |  |  _ \(_) __ _(_) |_ __ _| |
 | || |_    / _ \ | | |  | | | | |/ _  | | __/ _  | |
 |__   _|  / ___ \| | |  | |_| | | (_| | | || (_| | |
    |_|   /_/   \_\_|_|  |____/|_|\__, |_|\__\__,_|_|
                                  |___/

EOF

echo -e "${GREEN}DEMO TEST${NC}"

./bin/drudock about

./bin/drudock --version
./bin/drudock app:init:build --appname travisapp --type D8 --dist Development --src New --apphost drudock.localhost --services "PHP,NGINX,MYSQL" && \
./bin/drudock nginx:proxy:start && \
which docker && \
docker ps && \
curl http://localhost > ./logs/travis-sample.html
