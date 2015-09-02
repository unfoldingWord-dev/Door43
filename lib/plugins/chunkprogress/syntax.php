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
require_once "report_activity_by_user.php";
require_once "report_activity_by_namespace.php";

/* Array of all possible reports */
global $CHUNKPROGRESS_REPORT_TYPES;
$CHUNKPROGRESS_REPORT_TYPES = array("activity_by_user");


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
     * @return array The info block for this plugin
     */
    public function getInfo()
    {
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }

    /**
     * Gets the type for this plugin
     * @return string
     */
    public function getType()
    {
        return "substition";
    }

    /**
     * Gets the paragraph type of this plugin
     * @return string
     */
    public function getPType()
    {
        return "block";
    }

    /**
     * Gets the sort order for this plugin
     * @return int The sort order (1 for now, 999 doesn't work)
     */
    public function getSort()
    {
        return 1;
    }

    /**
     * Connects the plugin to the text that triggers it
     * @param string $mode (TODO: not sure what this is for)
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern(
            '\{\{chunkprogress>[^}]*\}\}', $mode, 'plugin_chunkprogress'
        );
    }


    /**
     * Pulls the data necessary for rendering
     * @param string       $match   the text matched by the pattern
     * @param string       $state   The type of pattern
     * @param int          $pos     character position of matched text
     * @param Doku_Handler $handler ref to the Doku_Handler object
     * @return array The parameters for render()
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        global $CHUNKPROGRESS_REPORT_TYPES;

        $handle_start_timestamp = microtime(true);

        $expected_params = array(
            "report" => "",
            "namespace" => "",
            "start_date" => "",
            "end_date" => "",
            "users" => "",
            "debug" => "false"
        );

        // Extract parameters from match object
        /* @var $params array() */
        $params = getParams($match, $expected_params);

        // Validate report type
        if ($params["report"] == "") {
            // Blank report
            $params["message"]
                = "ERROR: Please set the 'report' parameter"
                . " to one of the following: "
                . implode(", ", $CHUNKPROGRESS_REPORT_TYPES) . ".";

        } else if ($params["report"] == "activity_by_user") {
            // Activity by user
            $params = handleActivityByUserReport($params);

        } else if ($params["report"] == "activity_by_namespace") {
            // Activity by user
            $params = handleActivityByNamespaceReport($params);

        } else {
            // Unrecognized report
            $params["message"]
                = "ERROR: Unrecognized 'report' parameter '"
                . $params["report"]
                . "' (maybe you misspelled it?) Valid values for 'report' are: "
                . implode(", ", $CHUNKPROGRESS_REPORT_TYPES) . ".";
            return $params;

        }

        $params["debug_handle_elapsed_time"]
            = round(microtime(true) - $handle_start_timestamp, 3);

        return $params;
    }

    /**
     * Renders the data to the page
     * @param string        $mode     Name of the format mode
     * @param Doku_Renderer $renderer ref to the Doku_Renderer
     * @param array         $params   Parameter object returned by handle()
     * @return bool|void
     */
    public function render($mode, Doku_Renderer $renderer, $params)
    {
        $render_start_timestamp = microtime(true);

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

        // Render report if recognized
        if ($params["report"] == "activity_by_user") {

            // Activity by user
            renderActivityByUserReport($mode, $renderer, $params);

        } else if ($params["report"] == "activity_by_namespace") {

            // Activity by namespace
            renderActivityByNamespaceReport($mode, $renderer, $params);

        }

        // Done (except for debug)
        $params["debug_render_elapsed_time"]
            = round(microtime(true) - $render_start_timestamp, 3);

        // Dump params if in debug mode
        if ($params["debug"] == "true") {

            $renderer->hr();

            $renderer->p_open();
            $renderer->emphasis_open();
            $renderer->unformatted("Debug: parameter dump");
            $renderer->emphasis_close();
            $renderer->p_close();

            $renderer->table_open();

            $renderer->tablerow_open();

            $renderer->tablecell_open();
            $renderer->strong_open();
            $renderer->unformatted("Key");
            $renderer->strong_close();
            $renderer->tablecell_close();

            $renderer->tablecell_open();
            $renderer->strong_open();
            $renderer->unformatted("Value");
            $renderer->strong_close();
            $renderer->tablecell_close();

            $renderer->tablerow_close();

            foreach ($params as $key => $value) {
                $renderer->tablerow_open();
                $renderer->tablecell_open();
                $renderer->unformatted($key);
                $renderer->tablecell_close();
                $renderer->tablecell_open();
                if (is_array($value)) {
                    // $renderer->unformatted("Array length " . count($value));
                    $renderer->unformatted(json_encode($value));
                } else {
                    $renderer->unformatted($value);
                }
                $renderer->tablecell_close();
                $renderer->tablerow_close();
            }
            $renderer->table_close();
        }

    }

}

