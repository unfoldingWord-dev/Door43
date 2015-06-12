<?php
/**
 * Name: CrossOriginRequest.php
 * Description: A Dokuwiki action plugin to perform cross-origin requests for the browser.
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
$door43shared->loadAjaxHelper();

class action_plugin_door43shared_CrossOriginRequest extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'cross_origin_request', array($this, 'execute_request'));
    }

    public function execute_request() {

        global $INPUT;

        // if no contentType was passed, use application/json as the default
        $contentType = $INPUT->str('contentType');
        if (empty($contentType)) $contentType = 'application/json';

        header('Content-Type: ' . $contentType);

        $http = new DokuHTTPClient();

        // Get the list of source languages that are level 3.
        $url = $INPUT->str('requestUrl');
        echo $http->get($url);
    }
}
