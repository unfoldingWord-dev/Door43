<?php

/**
 * PHP version 5
 *
 * Activity by namespace report
 *
 * @category Door43
 * @package  Chunkprogress
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */

require_once "utils.php";

/**
 * Convenience function to get just the status tags from a page revision.
 *
 * @param array $params The parameters given by the user
 *
 * @return An updated $params array with data filled in
 */
function handleActivityByNamespaceReport($params)
{
    global $cache_revinfo;

    // Validate parameters
    $params = validateNamespace($params);
    $params = validateStartDate($params);
    $params = validateEndDate($params);


    $namespace = $params["namespace"];
    $params["report_title"]
        = "Activity by Namespace for " . $namespace
        . " from " .  $params["start_date"] . " to " . $params["end_date"];

    // Find all pages in the namespace
    // Example page attributes are:
    // id: en:bible:notes:rom:01:01
    // rev: 1437676066
    // mtime: 1437676066
    // size: 3485
    $pages = getAllPagesInNamespace($namespace);
    $params["debug_num_pages_in_ns"] = count($pages);

    $sub_namespaces = array();
    foreach ($pages as $page) {

        // Ignore any pages that haven't been changed since the begin date.
        if ($page["rev"] < $params["start_timestamp"]) {
            continue;
        }

        // Clear the Dokuwiki revision cache.  This is potentially fragile, but 
        // we don't do this, the DokuWiki cache for this script will grow until 
        // it blows out the memory for this thread.
        $cache_revinfo = array();

        // Because we searched for all pages in $namespace, we can make the 
        // assumption that every page begins with the namespace.  Thus by 
        // removing the namespace from every page we get the path of the page 
        // within that namespace.  We use stren() + 1 to also catch the colon at 
        // the end of the parent namespace.
        $local_page_id = substr($page["id"], strlen($namespace) + 1);
        // error_log($page["id"] . " -> " . $local_page_id);
        $local_page_id_parts = explode(":", $local_page_id);
        // error_log($page["id"] . " -> " . $local_page_id_parts[0]);
        // The sub-namespace corresponds to the "book" if using a namespace like 
        // "en:bible:notes".
        $local_page_sub_namespace = $local_page_id_parts[0];
        if (in_array($local_page_sub_namespace, $sub_namespaces) == false) {
            array_push($sub_namespaces, $local_page_sub_namespace);
            // error_log("Added " . $local_page_sub_namespace);
            // error_log("Size of sub_namespaces: " . count($sub_namespaces));
        }

    }

    // Debug
    foreach ($sub_namespaces as $sub_namespace) {
        error_log($sub_namespace);
    }

    return $params;
}


/**
 * Renders activity report to the page
 * @param string $mode     Name of the format mode
 * @param obj    $renderer ref to the Doku_Renderer
 * @param obj    $params   Parameter object returned by handle()
 * @return Nothing?
 */
function renderActivityByNamespaceReport($mode, &$renderer, $params)
{
    global $CHUNKPROGRESS_STATUS_TAGS;

    $renderer->table_open();

    $renderer->tablerow_open();

    $renderer->tablecell_open();
    $renderer->strong_open();
    $renderer->unformatted("Namespace");
    $renderer->strong_close();
    $renderer->tablecell_close();

    foreach ($CHUNKPROGRESS_STATUS_TAGS as $status) {
        $renderer->tablecell_open();
        $renderer->strong_open();
        $renderer->unformatted($status);
        $renderer->strong_close();
        $renderer->tablecell_close();
    }

    $renderer->tablerow_close();

    // $user_status_count = $params["user_status_count"];
    // foreach ($user_status_count as $user => $statuses) {
    //     $renderer->tablerow_open();
    //     $renderer->tablecell_open();
    //     $renderer->unformatted($user);
    //     $renderer->tablecell_close();
    //     foreach ($CHUNKPROGRESS_STATUS_TAGS as $status) {
    //         $renderer->tablecell_open();
    //         if (array_key_exists($status, $statuses)) {
    //             $renderer->unformatted($statuses[$status]);
    //         }
    //         $renderer->tablecell_close();
    //     }
    //     $renderer->tablerow_close();
    // }

    $renderer->table_close();

}

