<?php
/**
 * Name: plugin_base.php
 * Description: A base class for the syntax plugins.
 *
 * Author: Phil Hopper
 * Date:   2015-05-20
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();



class Door43_Action_Plugin extends DokuWiki_Action_Plugin {

    protected $root;

    function __construct() {

        // get the plugin root dir
        $ref = new ReflectionClass($this);
        $this->root = dirname($ref->getFileName());
        $pos = strpos($this->root, DS . 'action');
        if ($pos !== false) {
            $this->root = substr($this->root, 0, $pos);
        }
    }

    /**
     * Strings that need translated are delimited by @ symbols. The text between the symbols is the key in lang.php.
     * @param $html
     * @return mixed
     */
    protected function translateHtml($html) {

        /* @var $door43shared helper_plugin_door43shared */
        global $door43shared;

        // $door43shared is a global instance, and can be used by any of the door43 plugins
        if (empty($door43shared)) {
            $door43shared = plugin_load('helper', 'door43shared');
        }

        if (!$this->localised) $this->setupLocale();
        return $door43shared->translateHtml($html, $this->lang);
    }
}
