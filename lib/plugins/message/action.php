<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Etienne M. <emauvaisfr@yahoo.fr>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_message extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo() {
        return array(
                'author' => 'Etienne M.',
                'email'  => 'emauvaisfr@yahoo.fr',
                'date'   => @file_get_contents(DOKU_PLUGIN.'message/VERSION'),
                'name'   => 'message Plugin',
                'url'   => 'http://www.dokuwiki.org/plugin:message',
                );
    }

    /**
     * Constructor
     */
    function action_plugin_message() {
      $this->setupLocale();
    }
                              
    /**
     * register the eventhandlers
     */
    function register(&$contr) {
        $contr->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_display_message', array());
    }

    function _display_message(&$event, $param) {
        global $conf;

        $file=$conf['cachedir'].'/message_error.txt';
        if (file_exists($file) && filesize($file)) msg(@file_get_contents($file),-1);

        $file=$conf['cachedir'].'/message_info.txt';
        if (file_exists($file) && filesize($file)) msg(@file_get_contents($file),0);

        $file=$conf['cachedir'].'/message_valid.txt';
        if (file_exists($file) && filesize($file)) msg(@file_get_contents($file),1);

        $file=$conf['cachedir'].'/message_remind.txt';
        if (file_exists($file) && filesize($file)) msg(@file_get_contents($file),2);

        return;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
