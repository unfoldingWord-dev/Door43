<?php
/**
 * Name: WordCounts.php
 * Description: Class to display the word counts from the tD api
 *
 * Author: Phil Hopper
 * Date:   11/18/15
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
 * Class to display the word counts from the tD api
 */
class syntax_plugin_door43counts_WordCounts extends Door43_Syntax_Plugin {

    function __construct() {
        parent::__construct('word_counts', 'word_counts.php');
    }
}
