#!/usr/bin/env bash
# -*- coding: utf8 -*-
#
#  Copyright (c) 2015 unfoldingWord
#  http://creativecommons.org/licenses/MIT/
#  See LICENSE file for details.
#
#  Contributors:
#  Richard Mahn <richard_mahn@wycliffeassociates.org>
#
#  Usage: update_languages.sh (that's it, will find your language repos and update all of them)

# DEBUG - true/false -  If true, will run "set -x"
: ${DEBUG:=false}

# If running in DEBUG mode, output information about every command being run
$DEBUG && set -x

: ${DOOR43_DIR:=$(cd $(dirname "$0") > /dev/null && pwd)}

if [ -z $1 ];
then
  # Let the person running the script know what's going on.
  echo "Pulling in latest changes for all language repositories..."

  # going to the parent directory
  pushd "$DOOR43_DIR/data/gitrepo/pages" > /dev/null

  # Find all git repositories and update it to the master latest revision
  for dir in $(find . -maxdepth 2 -type d -name ".git" | cut -c 3- | sort -u); do
      echo "";
      echo "Updateing $(dirname "$dir")";

      # We have to go to the .git parent directory to call the pull command
      pushd "$dir/.." > /dev/null;

      # we don't want any changes in a development language branch
      git fetch --all
      git reset --hard origin/master

      # lets get back main directory
      popd > /dev/null
  done

  popd > /dev/null
else 
  for arg in "$@"
  do
    dir="$DOOR43_DIR/data/gitrepo/pages/$arg"
echo $dir
    if [ ! -e $dir ];
    then
      echo "No such language: $arg";
      exit 1;
    fi

    pushd $dir > /dev/null
    
    git fetch --all
    git reset --hard origin/master

    popd > /dev/null
  done
fi

echo "Done!"
echo ""

