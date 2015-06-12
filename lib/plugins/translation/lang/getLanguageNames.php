<?php
/**
 * Name: getLanguageNames.php
 * Description: Rebuilds the langnames.txt file.
 *
 * RUN FROM COMMAND LINE
 *
 * Created by PhpStorm.
 *
 * Author: Phil Hopper
 * Date:   2/5/15
 * Time:   3:42 PM
 */

// must NOT be run within Dokuwiki
if(defined('DOKU_INC')) die();

// get the list of languages
$sourceUrl = 'http://td.unfoldingword.org/exports/langnames.json';
echo "Getting list from {$sourceUrl}\n";
$text = file_get_contents($sourceUrl);
$langNames = json_decode($text, true);

// rename the old file
$fileName = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'langnames.txt';
if (file_exists($fileName)) {

    echo "Backing up old file\n";
    $backupFileName = $fileName . '.bak';
    if (file_exists($backupFileName))
        unlink($backupFileName);

    rename($fileName, $backupFileName);
}

// create the new file
echo "Creating new file\n";
$file = fopen($fileName, 'w');
fwrite($file, "# Native language names\n");
fwrite($file, "# extracted from {$sourceUrl}\n\n");

// write the language code and name
foreach ($langNames as $langName) {

    fwrite($file, "{$langName['lc']}\t{$langName['ln']}\n");
}

fclose($file);

echo "Finished\n";
