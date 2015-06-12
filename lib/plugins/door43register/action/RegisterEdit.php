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

class action_plugin_door43register_RegisterEdit extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call_unknown');
    }

    /**
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_ajax_call_unknown(Doku_Event &$event, /** @noinspection PhpUnusedParameterInspection */ $param) {

        if ($event->data !== 'door43_register_edit') return;

        //no other action handlers needed
        $event->stopPropagation();
        $event->preventDefault();

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
