<?php
/**
 * Name: CreateObsTnNow.php
 * Description: A Dokuwiki syntax plugin to display a button the user can click to initialize OBS Notes
 * in another language.
 *
 * Author: Phil Hopper
 * Date:   2015-07-29
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
 * Class to display a button the user can click to initialize OBS in another language
 */
class syntax_plugin_door43obs_CreateObsNotes extends Door43_Syntax_Plugin {

    function __construct() {
        parent::__construct('obscreatenotes', 'button_obs_notes.html');
    }
}
