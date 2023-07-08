#!/bin/sh
set -e

# Initialise some variables
SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
BUILD_LATEST=false
TEST_ONLY=false
DO_NOT_CLEANUP=false

# Information about the container
GROUP_NAME="han"
APP_NAME="han-cloud-db"
APP_DIR="$SCRIPT_DIR/../../app"

# docker repo image location
REPO="docker.sysdomain.local/$GROUP_NAME/$APP_NAME"

function show_help {
cat <<\USAGE
 USAGE: ./build.sh dockertag
        builds the han ui to the specified commit or tag
USAGE
};


# dockertag=$1


function oldmain {
  echo "Remove old SQL bits"
  rm -rf sql
  echo "Copying SQL bits"
  cp -r ../../app/api/sql sql

  echo "Building docker image han-cloud-db:$dockertag"
  docker build -t han-cloud-db:$dockertag .
  echo "Tagging image"
  docker tag han-cloud-db:$dockertag smartnetworks/han:cloud-db-$dockertag
  echo "Pushing image to registry"
  docker push smartnetworks/han:cloud-db-$dockertag
  echo "Cleaning up"
  rm -rf sql
}


function main {

  GIT_HASH=$(git rev-parse --short HEAD)
  GIT_TAG=$(cd .. && git describe --always)

  #if [[ $GIT_TAG =~ $APP_NAME ]]; then
  #  APP_VERSION=${GIT_TAG/"$APP_NAME."/""}
  #else
  #  echo "Incorrect tag detected please fix before proceding"
  #  exit 1
  #fi

  APP_VERSION=${GIT_TAG/"$APP_NAME."/""}
  VERSION="$APP_VERSION-$GIT_HASH"

  IMAGE_FULL_TAG=$REPO:$VERSION
  IMAGE_SHORT_TAG=$REPO:$APP_VERSION

  # Some scripts bedore building container
  if [ -d "$SCRIPT_DIR/sql" ]; then
    rm -rf $SCRIPT_DIR/sql
  fi
  #mkdir $SCRIPT_DIR/sql
  cp -r $APP_DIR/api/sql ./sql

  # Build contaner
  echo "Building $IMAGE_FULL_TAG"
  docker build --pull -t $IMAGE_FULL_TAG .

  # Tag to version
  echo "Tagging $IMAGE_FULL_TAG as $IMAGE_SHORT_TAG"
  docker tag $IMAGE_FULL_TAG $IMAGE_SHORT_TAG

  if $BUILD_LATEST; then
    # Tag to latest
    echo "Tagging $IMAGE_FULL_TAG as $$REPO:latest"
    docker tag $IMAGE_FULL_TAG $REPO:latest
  fi
  if $TEST_ONLY; then
    echo "Testing only"
  else
    # Push to repo
    echo "Pushing $IMAGE_FULL_TAG"
    docker push $IMAGE_FULL_TAG
    echo "Pushing $IMAGE_SHORT_TAG"
    docker push $IMAGE_SHORT_TAG

    if $BUILD_LATEST; then
      echo "Pushing image as latest"
      # Push latest
      docker push $REPO:latest
    fi
  fi

  if $DO_NOT_CLEANUP; then
    echo "Not performing post build cleanup"
  else
    if $TEST_ONLY; then
      echo "Removing containers"
      docker rmi $IMAGE_FULL_TAG
    else
      echo "Removing containers"
      if $BUILD_LATEST; then
        docker rmi $IMAGE_FULL_TAG $IMAGE_SHORT_TAG $REPO:latest
      else
        docker rmi $IMAGE_FULL_TAG $IMAGE_SHORT_TAG
      fi
    fi
    echo "Removing tmp sql files"
    rm -rf $SCRIPT_DIR/sql
  fi
}


POSITIONAL=()
for i in "$@"; do
  case $i in
    -h | --help)
      show_help
      exit
      ;;
   --build-latest)
      BUILD_LATEST=true
      shift
      ;;
    --test-only)
      TEST_ONLY=true
      shift
      ;;
    --do-not-cleanup)
      DO_NOT_CLEANUP=true
      shift
      ;;
    --)
      shift
      break
      ;;
    -?*)                  # unknown option
      POSITIONAL+=("$1")  # save it in an array for later
      shift
      ;;
    *)
      break
      ;;
  esac
done


main "$@"




if [[ ${1:-} ]] && declare -F | cut -d' ' -f3 | fgrep -qx -- "${1:-}"
then "$@"
else main "$@"
fi
