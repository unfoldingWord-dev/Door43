<?php
/**
 * Name: DownloadDoc.php
 * Description: A Dokuwiki syntax plugin to allow the user to download OBS doc files.
 *
 * Author: Phil Hopper
 * Date:   2015-05-23
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadPluginBase();

/**
 * Class to allow the user to download OBS doc files
 */
class syntax_plugin_door43obsdocupload_DownloadDocFile extends Door43_Syntax_Plugin {

    function __construct() {
        parent::__construct('obsDocDownload', '');
    }
}
