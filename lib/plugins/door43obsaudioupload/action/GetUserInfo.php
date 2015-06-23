<?php
/**
 * Name: GetUserInfo.php
 * Description: A Dokuwiki action plugin to load the S3 config for the browser.
 *
 * Author: Phil Hopper
 * Date:   2015-02-10
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadAjaxHelper();

class action_plugin_door43obsaudioupload_GetUserInfo extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'obsaudioupload_user_info_request', array($this, 'handle_ajax_call'));
    }

    public function handle_ajax_call() {

        // read the config file
        $userInfo = $GLOBALS['USERINFO'];
        if (empty($userInfo))
            $userInfo = array('name' => '');
        else
            unset($userInfo['pass']);

        header('Content-Type: application/json');

        echo json_encode($userInfo);
    }
}
