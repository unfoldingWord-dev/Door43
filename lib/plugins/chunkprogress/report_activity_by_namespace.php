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


    $params = validateNamespace($params);
    $namespace = $params["namespace"];

    $params = validateStartDate($params);
    $start_timestamp = $params["start_timestamp"];

    $params = validateEndDate($params);

    $params["report_title"]
        = "Activity by Namespace for " . $namespace
        . " from " .  $params["start_date"] . " to " . $params["end_date"];

    // Find all pages in the namespace
    $pages = getAllPagesInNamespace($namespace);
    $params["debug_num_pages_in_ns"] = count($pages);


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

