#!/bin/bash

version=$1
if [ -z "$1" ]
  then
    echo "########\nPull DB image\n########\nUsage: ./pull-db.sh version\nExample:\n./pull-db.sh 001\n"
fi
image="smartnetworks/han:cloud-db-$version"
echo "Pulling docker image $image"
docker pull $image
