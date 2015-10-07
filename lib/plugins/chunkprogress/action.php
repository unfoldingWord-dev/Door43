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
        // error_log("--------------- chunkprogress:action.php:bypass_cache()");
        // error_log("ID: " . $ID);
        // error_log("INFO['id']: " . $INFO["id"]);
        if ($INFO["id"] != null && $INFO["id"] != $ID) {
            // error_log("Not bypassing cache");
            return;
        }
        // error_log("This is a page with potentiall diff links -- bypassing cache");
        $event->preventDefault();
        $event->stopPropagation();
        $event->result = false;
        $this->action_event = $event;
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
        global $conf;

        // Don't process sidebar-type pages, only the main page.
        // In sidebar pages, the $INFO["id"] is set to the main page.
        // On the main page, the $ID is set but the $INFO["id"] is null.
        // error_log("--------------- chunkprogress:action.php:handle_parser_wikitext_preprocess()");
        // error_log("ID: " . $ID);
        // error_log("INFO['id']: " . $INFO["id"]);
        if ($INFO["id"] != null && $INFO["id"] != $ID) {
            // error_log("Diff links won't be processed for this page.");
            return;
        }

        // Get namespace for this page
        $namespace = getNS(cleanID(getID()));
        // error_log("Namespace: " . $namespace);

        // Scan all pages in namespace to find previous and next chunks
        // Only search depth 1 since all we care about is siblings
        $pages_in_ns = getAllPagesInNamespace($namespace, 1);
        $previous_chunk_id = null;
        $next_chunk_id = null;
        $prior_page = null;
        foreach ($pages_in_ns as $page_info) {
            $page_id = $page_info["id"];
            // // error_log("page_id: $page_id");
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
            if ($diff_links != "") {
                $event->data = $event->data .
                    "\n  * [[$previous_chunk_id|Prev chunk]]: ";
                $event->data = $event->data . $diff_links;
            }
        }

        // Show links for this chunk
        $diff_links = generateDiffLinks($ID);
        if ($diff_links != "") {
            $event->data = $event->data .
                "\n  * [[$ID|This chunk]]: ";
            $event->data = $event->data . $diff_links;
        }

        // Show links for next chunk, if it exists
        if ($next_chunk_id != null) {
            $diff_links = generateDiffLinks($next_chunk_id);
            if ($diff_links != "") {
                $event->data = $event->data .
                    "\n  * [[$next_chunk_id|Next chunk]]: ";
                $event->data = $event->data . $diff_links;
            }
        }

    }
}

/* vim: set foldmethod=indent : */
