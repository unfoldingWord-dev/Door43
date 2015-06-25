#!/bin/bash

thisDir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
parentDir="$( dirname "${thisDir}" )"

lastFile="${thisDir}/.lastTscCheck"

if ! [ -f ${lastFile} ]
then
	echo "1409590000" > "$lastFile"
fi

current=`date +%s`
last=`cat ${thisDir}/.lastTscCheck`

for file in ${thisDir}/ts/*.ts
do
	modified=`stat -c "%Y" ${file}`
	if [ ${modified} -gt ${last} ]
	then
		tsc --sourcemap "$file" --outDir ${parentDir}
		echo "$file"
	fi
done
echo "finished"
echo "$current" > "$lastFile"
