<?php
// This file contains changes to local.php that you do NOT want in github, so they are private. These settings will be loaded after local.php is loaded,
// overwriting anything in local.php with the same array key.

// Changes pages and media dirs to ./data/gitrepo/*
$conf['datadir'] = './data/gitrepo/pages';
$conf['mediadir'] = './data/gitrepo/media';
