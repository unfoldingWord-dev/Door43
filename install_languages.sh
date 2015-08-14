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
#  Ussage: install_languages.sh [IETF-code [IETF-code...]]
#
#  Defaults to installing am, en, es, fr, and pt-br

# DEBUG - true/false -  If true, will run "set -x"
: ${DEBUG:=false}

# If running in DEBUG mode, output information about every command being run
$DEBUG && set -x

: ${DOOR43_DIR:=$(cd $(dirname "$0") > /dev/null && pwd)}

if [ -z $1 ];
then
	# default installed languages
	LANGUAGES=( 'am' 'en' 'es' 'fr' 'pt-br' )
else
	LANGUAGES=($@)
fi

pushd $DOOR43_DIR > /dev/null

echo "About pull these language repos: ${LANGUAGES[*]}"
read -r  -n 1 -p "Do you want to pull languages repos (Can take some time)? [y/N] " response
echo ""
echo ""
case $response in
    [yY][eE][sS]|[yY])
		echo 'Checking out language repos...';
		for lang in "${LANGUAGES[@]}"
		do
   			echo "Cloning d43-${lang} into data/gitrepo/pages/${lang}"
   			git clone "git@github.com:Door43/d43-${lang}" "data/gitrepo/pages/${lang}"
		done
        ;;
    *)
		echo "Ok. If you want to install them later, either run"
		echo "  $DOOR43_DIR/install_language.sh <IETF-CODE> (can supply lang code, defaults to ${LANGUAGES[*]})"
		echo "or run:"
		echo "  git clone git@github.com:Door43/d43-<IETF-CODE> $DOOR43_DIR/data/gitrepo/pages/<IETF-CODE>"
        ;;
esac

echo "Installed Languages"
echo ""
