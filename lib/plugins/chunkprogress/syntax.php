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

    /* Array of all possible status tags, in the order they usually occur. */
    private static $_STATUS_TAGS = array(
        "draft", "check", "review", "text", "discuss", "publish");

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
            "page" => "",
            "debug" => "false"
        );

        // Extract parameters from match object
        $params = getParams($match, $expected_params);

        // Get page metadata
        if (strlen($params["page"]) > 0) {
            $metadata = p_get_metadata($params["page"]);
            if (array_key_exists("title", $metadata)) {
                $params["page_title"] = $metadata["title"];
            } else {
                $params["message"] .= "WARNING: Could not find a page named '" .
                    $params["page"] .
                    "'.  Did you use the form 'en:bible:notes:1ch:01:01'?";
            }
        }

        // Get page history
        if (strlen($params["page"]) > 0) {
            $page_id = $params["page"];

            // Get page revisions
            $page_revision_ids = getRevisions($page_id, 0, 10000);

            // Add the current version of the page to the top of the revisions
            array_unshift($page_revision_ids, "");

            // Get data about each revision in order from oldest to newest
            $page_revisions = array();
            $prev_page_status_tags = array();
            foreach (array_reverse($page_revision_ids) as $revision_id) {
                $page_revision_data = array();

                // Human-readable timestamp
                $page_revision_data["timestamp_readable"] 
                    = date("Y-m-d H:i:s", getPageTimestamp($page_id, $revision_id));

                // User
                $page_revision_data["user"] = getPageUser($page_id, $revision_id);

                // Get filename
                $page_revision_data["filename"] = wikiFN($page_id, $revision_id);

                // Search page text for tags
                $page_revision_data["tags"] = array();
                $lines = gzfile($page_revision_data["filename"]);
                $page_revision_data["tags"] = array();
                foreach ($lines as $line) {
                    $matches = array();
                    preg_match("/{{tag>([^}]*)}}/", strtolower($line), $matches);
                    if (count($matches) > 0) {
                        $tags = explode(" ", $matches[1]);
                        $page_revision_data["tags"] = $tags;
                    }
                }

                // Pull out the status-related tags.
                $page_revision_data["status_tags"]
                    = array_intersect(
                        self::$_STATUS_TAGS, $page_revision_data["tags"]
                    );

                // Add page to array if status has changed
                if ($page_revision_data["status_tags"] != $prev_page_status_tags) {
                    // Add this revision to the array
                    array_push($page_revisions, $page_revision_data);
                }

                // Remember page status for the future
                $prev_page_status_tags = $page_revision_data["status_tags"];

            }
            $params["page_revisions"] = $page_revisions;
        }



        return $params;
    }

    /**
     * Renders the data to the page
     * @param string $mode     Name of the format mode
     * @param obj    $renderer ref to the Doku_Renderer
     * @param obj    $params   Parameter object returned by handle()
     * @return the parameters for render()
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
        }

        // Print page title
        if (array_key_exists("page_title", $params)) {
            $renderer->header("Progress Report for " . $params["page_title"], 2, 0);
            $renderer->p_open();
            $renderer->unformatted("(Page id: " . $params["page"] . " )", 1);
            $renderer->p_close();
        }

        // Print raw revisions
        if (array_key_exists("page_revisions", $params)) {
            $page_revisions = $params["page_revisions"];

            $renderer->table_open();

            $renderer->tablerow_open();

            $renderer->tablecell_open();
            $renderer->strong_open();
            $renderer->unformatted("Date");
            $renderer->strong_close();
            $renderer->tablecell_close();

            $renderer->tablecell_open();
            $renderer->strong_open();
            $renderer->unformatted("User (if known)");
            $renderer->strong_close();
            $renderer->tablecell_close();

            $renderer->tablecell_open();
            $renderer->strong_open();
            $renderer->unformatted("New Status");
            $renderer->strong_close();
            $renderer->tablecell_close();

            $renderer->tablerow_close();

            foreach ($page_revisions as $revision) {
                $renderer->tablerow_open();

                //$renderer->tablecell_open();
                //$renderer->unformatted($revision["revision_id"]);
                //$renderer->tablecell_close();

                $renderer->tablecell_open();
                $renderer->unformatted($revision["timestamp_readable"]);
                $renderer->tablecell_close();

                //$renderer->tablecell_open();
                //$renderer->unformatted($revision["filename"]);
                //$renderer->tablecell_close();

                //$renderer->tablecell_open();
                //$renderer->unformatted(implode(", ", $revision["tags"]));
                //$renderer->tablecell_close();

                $renderer->tablecell_open();
                $renderer->unformatted($revision["user"]);
                $renderer->tablecell_close();

                $renderer->tablecell_open();
                $renderer->unformatted(implode(", ", $revision["status_tags"]));
                $renderer->tablecell_close();

                $renderer->tablerow_close();
            }
            $renderer->table_close();
        }


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
                    $renderer->unformatted("Array length " . count($value));
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

// vim: foldmethod=indent
