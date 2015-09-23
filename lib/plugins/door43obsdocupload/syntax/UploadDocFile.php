<?php
/**
 * Name: UploadDoc.php
 * Description: A Dokuwiki syntax plugin to allow the user to upload OBS doc files.
 *
 * Author: Phil Hopper
 * Date:   2015-05-20
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
 * Class to allow the user to upload OBS doc files
 */
class syntax_plugin_door43obsdocupload_UploadDocFile extends Door43_Syntax_Plugin {

    function __construct() {
        parent::__construct('obsdocupload', 'obs_import.php');

        // the user must be logged in to upload a file
        $userInfo = $GLOBALS['USERINFO'];
        if (empty($userInfo) || empty($userInfo['name'])) {
            $this->templateFileName = 'login_required.html';
        }
    }
}
