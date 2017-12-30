#!/usr/bin/env bash

log_event()
{
    EVENT=$1
    printf "\n\n------ %s ------\n\n" "${EVENT}"
}

log_event 'Downloading BETA from S3'

curl -O http://d1gem705zq3obi.cloudfront.net/drudock-beta.phar && \
mv drudock-beta.phar /usr/local/bin/drudock && \
chmod +x /usr/local/bin/drudock && \
drudock
