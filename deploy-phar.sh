#!/usr/bin/env bash

log_event()
{
    EVENT=$1
    printf "\n\n------ %s ------\n\n" "${EVENT}"
}

#log_event 'Building phar executable'
#/usr/local/bin/box build -v
#
#mv drudock.phar drudock-beta.phar
#
#log_event 'Pushing compiled app to S3'
#aws s3 cp drudock-beta.phar s3://drudock --region=eu-west-2 --acl=public-read

mkdir -p tests/
which curl
docker ps
PORT=`echo $(docker port $(docker ps --format {{.Names}} | grep nginx) 80) | cut -d : -f 2`
echo $PORT
curl http://drudock.dev:$PORT > homepage.html