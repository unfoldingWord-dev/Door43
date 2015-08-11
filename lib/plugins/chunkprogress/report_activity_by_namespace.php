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
    $num_revisions_within_dates = 0;
    $count_by_sub_namespace_then_status = array();
    $count_by_sub_namespace_then_status["TOTAL"] = array();
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
        $page_id = $page["id"];
        $local_page_id = substr($page_id, strlen($namespace) + 1);
        $local_page_id_parts = explode(":", $local_page_id);
        // The sub-namespace corresponds to the "book" if using a namespace like
        // "en:bible:notes".
        $sub_namespace = $local_page_id_parts[0];
        if (in_array($sub_namespace, $sub_namespaces) == false) {
            array_push($sub_namespaces, $sub_namespace);
        }

        // Get all revisions for this page.
        $revision_ids = getRevisions(
            $page_id,
            0,
            10000
        );

        // Reverse the array so that it goes least-recent to most-recent
        $revision_ids = array_reverse($revision_ids);

        // Push the current revision onto the stack.
        array_push($revision_ids, $page["rev"]);
        $current_revision_id = $page["rev"];

        // Count number of revisions for debugging
        $num_revisions += count($revision_ids);

        // Consider each revision
        $prev_status_tags = null;
        $prev_revision_id = null;
        foreach ($revision_ids as $revision_id) {
            if ($revision_id >= $params["start_timestamp"]
                and $revision_id <= $params["end_timestamp"]
            ) {
                // Count number of revisions for debugging
                $num_revisions_within_dates += 1;

                // Load status tags for previous version if necessary.  This 
                // happens when the current version is within the time window 
                // but the previous version was not.
                if ($prev_revision_id != null and $prev_status_tags == null) {
                    $prev_status_tags = getStatusTags($page_id, $prev_revision_id);
                }

                // Get status tags (we have to make the call differently based on 
                // whether or not this is the current revision)
                if ($revision_id == $current_revision_id) {
                    $status_tags = getStatusTags($page_id, "");
                } else {
                    $status_tags = getStatusTags($page_id, $revision_id);
                }

                // Compare status tags to see if they've changed.
                if ($prev_status_tags != null
                    and $status_tags != $prev_status_tags
                ) {
                    // Status tags have changed.

                    // Create sub-namespace in report if needed.
                    if (array_key_exists(
                        $sub_namespace, $count_by_sub_namespace_then_status
                    ) == false) {
                        $count_by_sub_namespace_then_status[$sub_namespace]
                            = array();
                    }

                    foreach ($status_tags as $status_tag) {

                        // Create status count if it doesn't already exist.
                        if (array_key_exists(
                            $status_tag, 
                            $count_by_sub_namespace_then_status[$sub_namespace]
                        ) == false) {
                            $count_by_sub_namespace_then_status
                                [$sub_namespace][$status_tag] = 0;
                        }

                        // Increment status count.
                        $count_by_sub_namespace_then_status
                            [$sub_namespace][$status_tag] += 1;
                       
                        // Create total status count if it doesn't already exist.
                        if (array_key_exists(
                            $status_tag, 
                            $count_by_sub_namespace_then_status["TOTAL"]
                        ) == false) {
                            $count_by_sub_namespace_then_status
                                ["TOTAL"][$status_tag] = 0;
                        }

                        // Increment total status count.
                        $count_by_sub_namespace_then_status
                            ["TOTAL"][$status_tag] += 1;
                    }
                    
                }

            }
            // Remember previous revision 
            $prev_revision_id = $revision_id;
            $prev_status_tags = $status_tags;
        }
    }

    // Move total line to the bottom
    $total_row = $count_by_sub_namespace_then_status["TOTAL"];
    unset($count_by_sub_namespace_then_status["TOTAL"]);
    $count_by_sub_namespace_then_status["TOTAL"] = $total_row;

    $params["debug_num_revisions_in_ns"] = $num_revisions;
    $params["debug_num_revisions_within_dates"] = $num_revisions_within_dates;
    $params["count_by_sub_namespace_then_status"] 
        = $count_by_sub_namespace_then_status;


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

    $count_by_sub_namespace_then_status 
        = $params["count_by_sub_namespace_then_status"];
    foreach ($count_by_sub_namespace_then_status as $sub_namespace => $statuses) {
        $renderer->tablerow_open();
        $renderer->tablecell_open();
        $renderer->unformatted($sub_namespace);
        $renderer->tablecell_close();
        foreach ($CHUNKPROGRESS_STATUS_TAGS as $status) {
            $renderer->tablecell_open();
            if (array_key_exists($status, $statuses)) {
                $renderer->unformatted($statuses[$status]);
            }
            $renderer->tablecell_close();
        }
        $renderer->tablerow_close();
    }

    $renderer->table_close();

}

