<?php

/**
 * PHP version 5
 *
 * Adds links to status version diffs
 *
 * @category Door43
 * @package  DokuWiki
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */

if (!defined('DOKU_INC')) {
    die();
}

require_once "utils.php";

/**
 * Creates links on pages with status tags allowing the user to view
 * a report of the differences
 *
 * @category Door43
 * @package  DokuWiki
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */
class action_plugin_chunkprogress extends DokuWiki_Action_Plugin
{
    /**
     * Registers this plugin
     *
     * @param object $controller The controller to register with
     *
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook(
            'PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this,
            'handle_parser_wikitext_preprocess'
        );
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'bypass_cache');
    }

    /**
     * Checks to see if we need to bypass the cache.  We only bypass the
     * cache if we are loading the main page -- we do this because we need
     * to dynamically regenerate the diff links.
     *
     * @param object $event The event data
     * @param array  $param Passed parameters
     *
     * @return void
     */
    function bypass_cache(&$event, $param) {
        global $INFO;
        global $ID;
        global $ACT;

        if ($this->should_process_page() == false) {
            return;
        }
        // error_log("BYPASS");

        $event->preventDefault();
        $event->stopPropagation();
        $event->result = false;
        $this->action_event = $event;
    }

    /**
     * Checks to see if we should add links to this page.  The rules are
     * different based on whether we're in diff mode, etc.  By default we
     * do nothing unless we know this is a page we want.
     *
     * @return true if the page should be processed, false otherwise
     */
    private function should_process_page() {
        global $INFO;
        global $ID;
        global $ACT;

        // error_log("----- should_process_page()");
        // error_log("ACT: $ACT");
        // error_log("ID: $ID");
        // error_log("INFO['id']: " . $INFO["id"]);

        if(strpos($_SERVER['REQUEST_URI'], '_export/xhtmlbody') >= 0)
            return false;

        // Whitelist -- reject pages that don't match regex
        $whitelisted = false;
        $whitelist_namespaces = array(
            "/en:bible:notes:.*/",
            "/en:obe:.*/",
            "/en:ta:workbench:.*/"
        );
        foreach ($whitelist_namespaces as $whitelist_namespace) {
            if (preg_match($whitelist_namespace, $ID)) {
                $whitelisted = true;
            }
        }
        if ($whitelisted == false) {
            // error_log("DO NOT PROCESS PAGE: namespace did not match whitelist");
            return false;
        }

        if ($ACT == "show" && $INFO["id"] == null) {
            return true;
        }
        if ($ACT == "diff" && $INFO["id"] == $ID) {
            return true;
        }

        // error_log("DO NOT PROCESS PAGE");
        return false;
    }

    /**
     * Adds links to pre-processed text
     *
     * @param object $event The event data
     * @param array  $param Passed parameters
     *
     * @return array The parameters for render()
     */
    public function handle_parser_wikitext_preprocess(Doku_Event &$event, $param) {
        global $INFO;
        global $ID;
        global $ACT;
        global $conf;

        if ($this->should_process_page() == false) {
            return;
        }
        // error_log("GEN LINKS");

        // Get namespace for this page
        $namespace = getNS(cleanID(getID()));

        // Scan all pages in namespace to find previous and next chunks
        // Only search depth 1 since all we care about is siblings
        $pages_in_ns = getAllPagesInNamespace($namespace, 1);
        $previous_chunk_id = null;
        $next_chunk_id = null;
        $prior_page = null;
        foreach ($pages_in_ns as $page_info) {
            $page_id = $page_info["id"];
            // error_log("page_id: $page_id");
            if ($page_id == $ID) {
                // This is the requested page, which means that the prior
                // page is the previous chunk
                $previous_chunk_id = $prior_page;
            } elseif ($prior_page == $ID) {
                // The prior page is the requested page, which means that
                // this is the next chunk
                $next_chunk_id = $page_id;
                // We don't need to search further
                break;
            }
            $prior_page = $page_id;
        }
        // error_log("Previous chunk ID: " . $previous_chunk_id);
        // error_log("This chunk: " . $ID);
        // error_log("Next chunk ID: " . $next_chunk_id);

        // Show links for previous chunk, if it exists
        if ($previous_chunk_id != null) {
            $diff_links = generateDiffLinks($previous_chunk_id);
            $event->data = $event->data . "\n  * [[$previous_chunk_id|Prev chunk]]: ";
            $event->data = $event->data . $diff_links;
        }

        // Show links for this chunk
        $diff_links = generateDiffLinks($ID);
        $event->data = $event->data . "\n  * [[$ID|This chunk]]: ";
        $event->data = $event->data . $diff_links;

        // Show links for next chunk, if it exists
        if ($next_chunk_id != null) {
            $diff_links = generateDiffLinks($next_chunk_id);
            $event->data = $event->data . "\n  * [[$next_chunk_id|Next chunk]]: ";
            $event->data = $event->data . $diff_links;
        }

    }
}

/* vim: set foldmethod=indent : */
