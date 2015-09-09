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
#  Updates your Door43 repo, submodules and language repos
#
#  Usage: door43_update.sh

# DEBUG - true/false -  If true, will run "set -x"
: ${DEBUG:=false}

# If running in DEBUG mode, output information about every command being run
$DEBUG && set -x

: ${DOOR43_DIR:=$(cd $(dirname "$0") > /dev/null && pwd)}

pushd $DOOR43_DIR > /dev/null

echo "Updating Submdoules..."
./update_submodules.sh

echo "Updating composer installed libraries..."
php composer.phar update

# To clear the cache all we need to do is touch the local.php config file
echo "Clearing cache..."
touch conf/local.php 

echo "If you want to update language content, please run $DOOR43_DIR/update_languages.sh"

popd > /dev/null

echo "Done!"
echo ""

