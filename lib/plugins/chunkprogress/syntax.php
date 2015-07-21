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
require_once DOKU_INC.'inc/changelog.php';


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
        $params = array(
            "page" => "",
            "debug" => "false"
        );

        // Extract parameter string from match
        $begin_pos = strpos($match, ">") + 1;
        $end_pos = strpos($match, "}") - 1;
        $all_params_string = substr($match, $begin_pos, $end_pos - $begin_pos + 1);

        // Process parameters
        try {
            foreach (explode("&", $all_params_string) as $param_string) {
                $param_pair = explode("=", $param_string);
                if (count($param_pair) == 2) {
                    $key = $param_pair[0];
                    $value = $param_pair[1];
                    if (array_key_exists($key, $params)) {
                        $params[$key] = $value;
                    } else {
                        $params["message"] .= 
                            "\nWARNING: didn't recognize parameter '" .
                            $key . "' (maybe you misspelled it?)";
                    }
                } else {
                    $params["message"] .= 
                        "\nWARNING: didn't understand parameter '" .
                        $param_string . "' (maybe you forgot the '=''?)";
                }
            }
        } catch (Exception $exception) {
            $params["message"] .= 
                "EXCEPTION: Please tell a developer about this: " . $e->getMessage();
        }


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
            $page_revision_ids = getRevisions($page_id, 0, 10000);
            array_unshift($page_revision_ids, "");
            $page_revisions =array();
            // Get data about each revision
            foreach (array_reverse($page_revision_ids) as $revision_id) {
                $page_revision_data = array();
                $page_revision_data["revision_id"] = $revision_id;
                $page_revision_data["timestamp_readable"] 
                    = date("Y-m-d H:i:s", $revision_id);
                $page_revision_data["filename"] = wikiFN($page_id, $revision_id);
                $page_revision_data["tags"] = array();
                // Search page text for tags
                $lines = gzfile($page_revision_data["filename"]);
                foreach ($lines as $line) {
                    $matches = array();
                    preg_match("/{{tag>([^}]*)}}/", strtolower($line), $matches);
                    if (count($matches) > 0) {
                        $tags = explode(" ", $matches[1]);
                        $page_revision_data["tags"] = $tags;
                    }
                }
                // Weed out any status tags we don't care about
                $page_status_tags = array_intersect(self::$_STATUS_TAGS, $tags);
                $page_revision_data["status_tags"] = $page_status_tags;
                // Add this revision to the array
                array_push($page_revisions, $page_revision_data);
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

                $renderer->tablecell_open();
                $renderer->unformatted(implode(", ", $revision["tags"]));
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
            $renderer->tablerow_close();
            $renderer->tablecell_close();
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
