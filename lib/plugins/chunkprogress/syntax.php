<?php

/**
 * PHP version 5
 *
 * Creates a progress report for a given chunk
 *
 * @category Door43
 * @package  DokuWiki
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */

// Must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
}
require_once DOKU_PLUGIN.'syntax.php';
require_once "utils.php";


/**
 * Creates a report showing the progress of a chunk through its stages.
 *
 * @category Door43
 * @package  DokuWiki
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */
class syntax_plugin_chunkprogress extends DokuWiki_Syntax_Plugin
{

    /**
     * Gets the info block for this plugin
     * @return the info block for this plugin
     */
    public function getInfo()
    {
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }

    /**
     * Gets the type for this plugin
     * @return "substition"
     */
    public function getType()
    {
        return "substition";
    }

    /**
     * Gets the paragraph type of this plugin
     * @return "block"
     */
    public function getPType()
    {
        return "block";
    }

    /**
     * Gets the sort order for this plugin
     * @return the sort order (1 for now, 999 doesn't work)
     */
    public function getSort()
    {
        return 1;
    }

    /**
     * Connects the plugin to the text that triggers it
     * @param string $mode (TODO: not sure what this is for)
     * @return None
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern(
            '\{\{chunkprogress>[^}]*\}\}', $mode, 'plugin_chunkprogress'
        );
    }


    /**
     * Pulls the data necessary for rendering
     * @param string $match   the text matched by the pattern
     * @param string $state   The type of pattern
     * @param int    $pos     character position of matched text
     * @param obj    $handler ref to the Doku_Handler object
     * @return the parameters for render()
     */
    public function handle($match, $state, $pos, &$handler)
    {
        $expected_params = array(
            "report" => "",
            "namespace" => "",
            "debug" => "false"
        );

        // Extract parameters from match object
        $params = getParams($match, $expected_params);

        // Validate report type
        if ($params["report"] == "") {
            $params["message"]
                = "ERROR: Please set the 'report' parameter to one of the following:"
                . " 'activity-by-user', ...";
        } else if ($params["report"] == "activity-by-user") {
            return handleActivityByUserReport($params);
        } else {
            $params["message"]
                = "ERROR: Unrecognized report type '"
                . $params["report"]
                . "' (maybe you misspelled it?)";
            return $params;
        }

        return $params;
    }

    /**
     * Renders the data to the page
     * @param string $mode     Name of the format mode
     * @param obj    $renderer ref to the Doku_Renderer
     * @param obj    $params   Parameter object returned by handle()
     * @return Nothing?
     */
    public function render($mode, &$renderer, $params)
    {
        // Print warnings or errors, if any
        if (array_key_exists("message", $params)) {
            $renderer->p_open();
            $renderer->strong_open();
            $renderer->unformatted($params["message"]);
            $renderer->strong_close();
            $renderer->p_close();
            return;
        }

        // Print page title
        if (array_key_exists("report_title", $params)) {
            $renderer->header($params["report_title"], 2, 0);
        }

    }

}

// vim: foldmethod=indent
