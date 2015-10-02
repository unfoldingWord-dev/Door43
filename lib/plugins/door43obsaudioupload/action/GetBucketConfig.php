<?php
/**
 * Name: GetBucketConfig.php
 * Description: A Dokuwiki action plugin to load the S3 config for the browser.
 *
 * Author: Phil Hopper
 * Date:   2015-01-06
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadAjaxHelper();

class action_plugin_door43obsaudioupload_GetBucketConfig extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'obsaudioupload_bucket_config_request', array($this, 'handle_ajax_call'));
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_do_action');
    }

    /**
     * Gets the S3 bucket config
     */
    public function handle_ajax_call() {

        // read the config file
        $config = json_decode(file_get_contents('/usr/share/httpd/.ssh/door43bucket.conf'));

        // do not send the secret key
        unset($config->secretKey);

        header('Content-Type: application/json');

        echo json_encode($config);
    }

    /**
     * Gets the S3 bucket config
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_do_action(Doku_Event &$event, $param) {

        if ($event->data !== 'obsaudioupload_signature_request') return;

        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        // read the config file
        $config = json_decode(file_get_contents('/usr/share/httpd/.ssh/door43bucket.conf'));

        // get the input
        $raw = file_get_contents('php://input');

        // output
        header('Content-Type: application/json');

        $policy = base64_encode(utf8_encode($raw));
        $signature = base64_encode(hash_hmac( 'sha1', $policy, $config->secretKey, true));
        $return = '{"policy": "' . $policy . '", "signature": "' . $signature . '"}';
        echo $return;
        exit();
    }
}
