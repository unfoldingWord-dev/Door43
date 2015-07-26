<?php
/**
 * Name: RegisterEdit.php
 * Description: A Dokuwiki action plugin to override the default register behavior.
 *
 * Author: Phil Hopper
 * Date:   2015-02-26
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadAjaxHelper();

class action_plugin_door43register_RegisterEdit extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'door43_register_edit', array($this, 'handle_ajax_call'));
    }

    public function handle_ajax_call() {

        global $INPUT;

        header('Content-Type: text/plain');

        $langCode = $INPUT->str('lang');
        $text = $INPUT->str('text');

        $dir = DOKU_CONF . 'lang/' . $langCode;
        $file = $dir .'/register.txt';

        // make sure the directory exists
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755) === false) {
                echo $this->getLang('makeDirError');
                return;
            }
        }

        // save the file
        if (file_put_contents($file, $text) === false) {
            echo $this->getLang('saveFileError');
            return;
        }

        // set file permissions
        chmod($file, 0644);

        // log the change
        $timestamp = time();
        $id = $langCode . ':register';
        addLogEntry($timestamp, $id);

        // save this revision in the attic
        $atticFile = wikiFN($id, $timestamp, true);
        io_saveFile($atticFile, $text, false);

        // send OK to the browser
        echo 'OK';
    }


}
