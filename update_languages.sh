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

# Let the person running the script know what's going on.
echo "Pulling in latest changes for all language repositories..."

# going to the parent directory
pushd "$DOOR43_DIR/data/gitrepo/pages" > /dev/null

# Find all git repositories and update it to the master latest revision
for dir in $(find . -maxdepth 2 -type d -name ".git" | cut -c 3-); do
    echo "";
    echo "Updateing $(dirname "$dir")";

    # We have to go to the .git parent directory to call the pull command
    pushd "$dir/.." > /dev/null;

    # finally pull
    git pull origin master;

    # lets get back main directory
    popd > /dev/null
done

popd > /dev/null

echo "Done!"
echo ""

