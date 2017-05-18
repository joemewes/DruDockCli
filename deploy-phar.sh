#!/usr/bin/env bash

log_event()
{
    EVENT=$1
    printf "\n\n------ %s ------\n\n" "${EVENT}"
}

log_event 'Building phar executable'
/usr/local/bin/box build -v

log_event 'Pushing compiled app to S3'
mv drudock.phar drudock-beta.phar
aws s3 cp drudock-beta.phar s3://drudock --region=eu-west-2 --acl=public-read
