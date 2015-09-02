<?php
/**
 * Name: DestinationLanguages.php
 * Description: A Dokuwiki syntax plugin to display a text box that allows the user to look up and select a language. The
 * list of languages comes from here: http://door43.org:9096/?q=
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
 * Class to retrieve Destination languages and display them in a text box element
 */
class syntax_plugin_door43obs_DestinationLanguages extends Door43_Syntax_Plugin {

    function __construct() {
        parent::__construct('obsdestinationlang', '');
    }

    protected function getTextToRender($match) {

        $html = '<label for="selectObsDestination">@destinationLabel@</label>&nbsp;';

        /* @var $translation helper_plugin_door43translation */
        $translation = plugin_load('helper','door43translation');
        $html .= $translation->renderAutoCompleteTextBox('selectObsDestination', 'selectObsDestination', 'width: 250px;');

        // Set the label text.
        // If the "special" tag was found, use the default text.
        if (preg_match('/' . str_replace('/', '\/', $this->specialMatch) . '/', $match))
            return $this->translateHtml($html);

        // If you are here, the "match" was the un-matched segment between the entry and exit tags,
        // which should be the desired label text.
        return str_replace('@destinationLabel@', $match, $html);
    }
}
