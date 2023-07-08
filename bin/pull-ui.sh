#!/bin/bash

version=$1
if [ -z "$1" ]
  then
    echo "########\nPull UI image\n########\nUsage: ./pull-ui.sh version\nExample:\n./pull-ui.sh 001\n"
fi
image="smartnetworks/han:cloud-ui-$version"
echo "Pulling docker image $image"
docker pull $image
