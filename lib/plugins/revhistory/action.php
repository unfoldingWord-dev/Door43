<?php
/**
 * DokuWiki Plugin revhistory
 *
 * @license     Copyright (c) 2015 unfoldingWord http://creativecommons.org/licenses/MIT/
 * @author      Johnathan Pulos <johnathan@missionaldigerati.org>
 */
/**
 * Die if DokuWiki is not running
 */
if(!defined('DOKU_INC')) die();

/**
 * Define a constant for the plugin directory
 */
if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}

/**
 * Require the DokuWiki_Action_Plugin
 */
require_once(DOKU_PLUGIN . 'action.php');

class action_plugin_revhistory extends DokuWiki_Action_Plugin
{
    /**
     * Are we testing this class.  If so, do not exit the code.
     *
     * @var boolean
     **/
    public $testing = false;
    /**
     * The JSON response that will be echoed out
     *
     * @var object
     **/
    public $response;
    /**
     * Register for actions on the Doku_Event_Handler
     *
     * @param  Doku_Event_Handler $controller The event handler for the DokuWiki
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleAction', array());
    }

    /**
     * Handle the action: revhistory.
     *
     * @param  Doku_Event   &$event The event that was triggered
     * @param  array        $param  The extra parameter array passed in the register_hook
     *
     * @return JSON Object The revision history for the given namespace
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function handleAction(&$event, $param)
    {
        global $conf;
        if ($event->data !== 'revhistory') {
            /**
             * We are not handling this action
             */
            return;
        }
        $response   = array();
        $startDate  = trim($_GET['start']);
        $endDate    = trim($_GET['end']);
        $namespace  = trim($_GET['ns']);

        if ($this->isPresent($startDate)) {
            $startDate = strtotime($startDate);
        }

        if ($this->isPresent($endDate)) {
            $endDate = strtotime($endDate);
        }

        if ($this->isPresent($namespace)) {
            $namespace = str_replace(':', '/', $namespace);
        }

        $this->response = json_encode($response);
        $this->sendResponse();
    }

    /**
     * Checks whether the string is present.  Fails if null or empty
     *
     * @param  string  $string The string to test
     *
     * @return boolean         Is it present?
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function isPresent($string)
    {
        return ((!is_null($string)) && ($string !== ''));
    }

    /**
     * Send the response to the screen, if we are not testing.  Warning, this function throws an exit.
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    protected function sendResponse()
    {
        if (!$this->testing) {
            header('Content-Type: application/json');
            echo $this->response;
            exit;
        }
    }
}