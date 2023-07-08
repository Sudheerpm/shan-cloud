#!/bin/bash

if [ -z "$1" ]
  then
    printf "######\nStart MySQL Database\n######\nUsage\n./start-db.sh docker-version\nExample:\n./start-db.sh 001\n"
    exit 1
fi

tag=$1
docker stop han-db
docker rm han-db
docker run \
  -d \
  -e MYSQL_ROOT_PASSWORD=welcome1 \
  -e MYSQL_USER=han \
  -e MYSQL_PASSWORD=welcome1 \
  -e MYSQL_DATABASE=han \
  --name han-db \
  --restart=unless-stopped \
  -v /opt/mysql:/var/lib/mysql \
  -p 3306:3306 \
  "smartnetworks/han:cloud-db-$tag"

