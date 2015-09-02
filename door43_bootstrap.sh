#!/usr/bin/env bash
# -*- coding: utf8 -*-
#
#  Copyright (c) 2015 unfoldingWord
#  http://creativecommons.org/licenses/MIT/
#  See LICENSE file for details.
#
#  Contributors:
#  Richard Mahn <richard_mahn@wycliffeassociates.org>

set -e

# DEBUG - true/false -  If true, will run "set -x"
: ${DEBUG:=false}

# If running in DEBUG mode, output information about every command being run
$DEBUG && set -x

# If not will exit
$DEBUG || trap 'popd > /dev/null' EXIT SIGHUP SIGTERM

: ${DOOR43_DIR:=$(cd $(dirname "$0") && pwd)}

pushd $DOOR43_DIR > /dev/null

echo 'Putting config files in place...'
cp conf/local.php.dev conf/local.php
cp conf/plugins.local.php.dev conf/plugins.local.php
cp conf/acl.auth.php.dev conf/acl.auth.php
cp conf/users.auth.php.dev conf/users.auth.php

echo 'Making git configurations...'
git config core.fileMode false

echo 'Pulling submodules...'
git submodule init
git submodule update

echo 'Running composer to install packages'
php composer.phar install

$DOOR43_DIR/install_languages.sh

echo "DONE!"
echo ""
