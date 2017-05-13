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
cd /Users/joe/dev/playground && \
rm -rf testdev && \
./bin/drudock env:init testdev --type D8 --dist Development --src New --apphost drudock.dev --services "UNISON,PHP,NGINX,MYSQL" && \
cd testdev && \
./bin/drudock build:init && \
curl -O http://localhost
