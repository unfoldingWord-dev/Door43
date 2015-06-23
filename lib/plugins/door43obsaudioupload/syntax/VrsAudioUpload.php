<?php
/**
 * Name: VrsUpload.php
 * Description: A Dokuwiki syntax plugin to display a page for uploading OBS audio files for VRS.
 *
 * Author: Phil Hopper
 * Date:   2015-01-04
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadPluginBase();

class syntax_plugin_door43obsaudioupload_VrsAudioUpload extends Door43_Syntax_Plugin {

    function __construct() {
        parent::__construct('obsvrsaudioupload', 'vrs_audio_upload.html');
    }

    protected function getTextToRender($match) {

        $html = '<label for="obsaudioupload-selectLanguageCode">@selectLanguage@</label>&nbsp;';

        /* @var $translation helper_plugin_translation */
        $translation = plugin_load('helper','translation');
        $html .= $translation->renderAutoCompleteTextBox('obsaudioupload-selectLanguageCode', 'obsaudioupload-selectLanguageCode', 'width: 250px;');

        // Set the label text.
        // If the "special" tag was found, use the default text.
        if (preg_match('/' . str_replace('/', '\/', $this->specialMatch) . '/', $match))
            $html = $this->translateHtml($html);

        // If you are here, the "match" was the un-matched segment between the entry and exit tags,
        // which should be the desired label text.
        $html = str_replace('@destinationLabel@', $match, $html);

        $returnVal = parent::getTextToRender($match);

        return str_replace('<!-- insert language selector here -->', $html, $returnVal);
    }
}
