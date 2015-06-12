<?php
/**
 * Name: SourceLanguages.php
 * Description: A Dokuwiki syntax plugin to display a dropdown box that allows the user to select a language. The source
 * languages comes from here: https://api.unfoldingword.org/obs/txt/1/obs-catalog.json
 *
 * Author: Phil Hopper
 * Date:   2014-12-10
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
 * Class to retrieve source languages and display them in a select element
 */
class syntax_plugin_door43obs_SourceLanguages extends Door43_Syntax_Plugin {

    function __construct() {
        parent::__construct('obssourcelang', 'source_language.html');
    }
}


