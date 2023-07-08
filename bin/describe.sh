#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
git log -n1 --pretty=format:%h%d > "$DIR/../app/tags.txt"
git describe --always > "$DIR/../app/version.txt"
