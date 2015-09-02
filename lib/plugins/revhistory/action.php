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
     * @link https://www.dokuwiki.org/tips:recreate_wiki_change_log
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
        $changes        = array();
        $success        = true;
        $errorMessage   = '';
        $startDate      = trim($_GET['start']);
        $endDate        = trim($_GET['end']);
        $includeMedia   = trim($_GET['media']);
        $namespace      = trim($_GET['ns']);
        $orderDirection = trim($_GET['order']);

        if ($this->isPresent($startDate)) {
            $startDate = (int)$startDate;
        }

        if ($this->isPresent($endDate)) {
            $endDate = (int)$endDate;
        }

        if ($this->isPresent($includeMedia)) {
            $includeMedia = ($includeMedia == '1') ? true : false;
        } else {
            $includeMedia = $this->getConf('listmedia');
        }

        if ((!$this->isPresent($orderDirection)) || (!in_array($orderDirection, array('desc', 'asc')))) {
            $orderDirection = $this->getConf('orderdefault');
        }

        try {
            $changes = $this->getChanges($conf, $startDate, $endDate, $namespace, $includeMedia, $orderDirection);  
        } catch (Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();
        }

        $this->response = json_encode(
            array(
                'success'           =>  ($success) ? 'success' : 'error',
                'error_message'     =>  $errorMessage,
                'changes'           =>  $changes
            )
        );

        $event->stopPropagation();
        $event->preventDefault();
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
     * get the changes from the change log
     *
     * @param  array    $config             An array of configuration setting from DokuWiki
     * @param  string   $startDate          A string representing a unix time stamp for the starting date
     * @param  string   $endDate            A string representing a unix time stamp for the ending date
     * @param  string   $namespace          The namespace for the given search
     * @param  boolean  $includeMedia       Do you want to include media file changes?
     * @param  string   $orderDirection     The direction to order the changes in
     *
     * @return array                        An Array of changes based on the given parameters
     * @access protected
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    protected function getChanges($config, $startDate, $endDate, $namespace, $includeMedia, $orderDirection)
    {
        $changes = array();
        $lines = array();
        if (file_exists($config['changelog'])) {
            $lines = file($config['changelog']);
        } else {
            return $changes;
        }

        /**
         * iterate over the lines in reverse order, since the latest is at the bottom of the file.
         */
        for ($i = count($lines) - 1; $i >= 0; $i--) { 
            $change = $this->parseLineForChange($lines[$i], $startDate, $endDate, $namespace);

            if ($change !== false) {
                $change['file_type'] = 'file';
                array_push($changes, $change);
            }
        }

        if (($includeMedia) && (file_exists($config['media_changelog']))) {
            $lines = array();
            $lines = file($config['media_changelog']);
            /**
             * iterate over the lines of media file in reverse order, since the latest is at the bottom of the file.
             */
            for ($i = count($lines) - 1; $i >= 0; $i--) { 
                $change = $this->parseLineForChange($lines[$i], $startDate, $endDate, $namespace);

                if ($change !== false) {
                    $change['file_type'] = 'media';
                    array_push($changes, $change);
                }
            }
        }
        return $this->sortChanges($changes, $orderDirection); 
    }

    /**
     * Parses the give line, and sends back the data if it meets the given criteria.  Returns null if
     * the line does not meet the criteria.
     *
     * @param  string   $line               The line to parse
     * @param  string   $startDate          A string representing a unix time stamp for the starting date
     * @param  string   $endDate            A string representing a unix time stamp for the ending date
     * @param  string   $namespace          The namespace for the given search
     *
     * @return array|false                  The data about the line or false if unreadable
     * @access protected
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    protected function parseLineForChange($line, $startDate, $endDate, $namespace)
    {
        $change = $this->parseLine($line);

        if ($change === flase) {
            return false;
        }

        if ($this->isPresent($namespace)) {
            /**
             * Only return lines in given namespace
             */
            $inNamespace = strrpos($change['id'], $namespace);
            if ((!$this->isPresent($change['id'])) || ($inNamespace === false)) {
                return false;
            }
        }

        if ($this->isPresent($startDate)) {
            /**
             * Only get changes passed the start date
             */
            if ($change['date'] < $startDate) {
                return false;
            }
        }

        if ($this->isPresent($endDate)) {
            /**
             * Only get changes prior to the end date
             */
            if ($change['date'] > $endDate) {
                return false;
            }
        }

        return $change;
    }

    /**
     * parse the line from DokuWiki.  This method comes from inc/changelog.php parseChangelogLine().
     *
     * @param  string $line     The line to parse
     *
     * @return array|bool       An array of the line data or false if unreadable
     * @access protected
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    protected function parseLine($line)
    {
        $tmp = explode("\t", $line);
        if ($tmp!==false && count($tmp)>1) {
            $info = array();
            $info['date']  = (int)trim($tmp[0]); // unix timestamp
            $info['ip']    = trim($tmp[1]); // IPv4 address (127.0.0.1)
            $info['type']  = trim($tmp[2]); // log line type
            $info['id']    = trim($tmp[3]); // page id
            $info['user']  = trim($tmp[4]); // user name
            $info['sum']   = trim($tmp[5]); // edit summary (or action reason)
            $info['extra'] = rtrim($tmp[6], "\n"); // extra data (varies by line type)
            return $info;
        } else {
            return false;
        }
    }

    /**
     * Sort the changes into ascending order of date
     *
     * @param  array    $changes    The array fo changes
     * @param  string   $order      The sort order to order the array in (desc or asc)
     *
     * @return array                A sorted version of the changes array
     * @access protected
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    protected function sortChanges($changes, $direction)
    {
        $dates = array();
        foreach ($changes as $change) {
            array_push($dates, $change['date']);
        }
        if ($direction == 'asc') {
            array_multisort($dates, SORT_ASC, $changes);
        } else {
            array_multisort($dates, SORT_DESC, $changes);
        }
        return $changes;
    }

    /**
     * Send the response to the screen, if we are not testing.  Warning, this function throws an exit.
     *
     * @return void
     * @access protected
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