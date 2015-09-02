<?php

/**
 * PHP version 5
 *
 * Activity by user report
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
 * @return array An updated $params array with data filled in
 */
function handleActivityByUserReport($params)
{
    global $cache_revinfo;

    $params = validateNamespace($params);
    $namespace = $params["namespace"];

    $params = validateStartDate($params);
    $start_timestamp = $params["start_timestamp"];

    $params = validateEndDate($params);

    $params["report_title"]
        = "Activity by User for " . $namespace
        . " from " .  $params["start_date"] . " to " . $params["end_date"];

    // Find all pages in the namespace
    $pages = getAllPagesInNamespace($namespace);
    $params["debug_num_pages_in_ns"] = count($pages);

    // Build an array of the last update by user by page
    $num_revisions = 0;
    $num_revisions_within_dates = 0;
    $num_revisions_with_matching_users = 0;

    $last_revision_by_user_by_page = array();
    $page_current_revisions = array();
    foreach ($pages as $page) {

        // Ignore any pages that haven't been changed since the begin date.
        if ($page["rev"] < $start_timestamp) {
            continue;
        }

        // Clear the Dokuwiki revision cache.  This is potentially fragile, but 
        // we don't do this, the DokuWiki cache for this script will grow until 
        // it blows out the memory for this thread.
        $cache_revinfo = array();

        // Get all revisions for this page.
        $page_id = $page["id"];
        $revision_ids = getRevisions(
            $page_id,
            0,
            10000
        );

        // Reverse the array so that it goes least-recent to most-recent
        $revision_ids = array_reverse($revision_ids);

        // Push the current revision onto the stack.
        array_push($revision_ids, $page["rev"]);
        $page_current_revisions[$page_id] = $page["rev"];

        // Count number of revisions for debugging
        $num_revisions += count($revision_ids);

        // Consider each revision
        foreach ($revision_ids as $revision_id) {
            if ($revision_id < $start_timestamp
                or $revision_id > $params["end_timestamp"]
            ) {
                // Ignore revisions that fall outside the date window
                continue;
            }

            // Count number of revisions for debugging
            $num_revisions_within_dates += 1;

            // Get info for this revision
            $user = getPageUser($page_id, $revision_id);

            // Filter on users.
            if ($user == "") {
                // Ignore empty users.
                continue;
            } elseif ($params["users"] != ""
                and in_array($user, $params["users"]) == false
            ) {
                // This user isn't in the list, ignore this revision
                continue;
            }
            $num_revisions_with_matching_users += 1;

            // Remember the most recent revision by user
            if (array_key_exists($user, $last_revision_by_user_by_page) == false) {
                $last_revision_by_user_by_page[$user] = array();
            }
            $last_revision_by_user_by_page[$user][$page_id] = $revision_id;
        }
    }
    $params["debug_num_revisions_in_ns"] = $num_revisions;
    $params["debug_num_revisions_within_dates"] = $num_revisions_within_dates;
    $params["debug_num_revisions_with_matching_users"]
        = $num_revisions_with_matching_users;

    // Identify status of last change per user
    $last_status_by_user_by_page = array();
    foreach ($last_revision_by_user_by_page as $user => $page_ids) {
        foreach ($page_ids as $page_id => $last_revision_id) {
            // $statuses = getStatusTags($page_id, $last_revision_id);
            // Get status tags (we have to make the call differently based on 
            // whether or not this is the current revision)
            if ($last_revision_id == $page_current_revisions[$page_id]) {
                $statuses = getStatusTags($page_id, "");
            } else {
                $statuses = getStatusTags($page_id, $last_revision_id);
            }
            if (count($statuses) > 0) {
                if (array_key_exists($user, $last_status_by_user_by_page) == false) {
                    $last_status_by_user_by_page[$user] = array();
                }
                $last_status_by_user_by_page[$user][$page_id]
                    = implode(", ", $statuses);
            }
        }
    }

    // Create count of status by user
    $count_by_user_then_status = array();
    $count_by_user_then_status["TOTAL"] = array();
    foreach ($last_status_by_user_by_page as $user => $page_ids) {
        foreach ($page_ids as $page_id => $last_status) {
            if (array_key_exists($user, $count_by_user_then_status) == false) {
                $count_by_user_then_status[$user] = array();
            }
            if (array_key_exists(
                $last_status, $count_by_user_then_status[$user]
            ) == false) {
                $count_by_user_then_status[$user][$last_status] = 0;
            }
            $count_by_user_then_status[$user][$last_status] += 1;
            if (array_key_exists(
                $last_status, $count_by_user_then_status["TOTAL"]
            ) == false) {
                $count_by_user_then_status["TOTAL"][$last_status] = 0;
            }
            $count_by_user_then_status["TOTAL"][$last_status] += 1;
        }
    }
    
    // Move total line to the bottom
    $total_row = $count_by_user_then_status["TOTAL"];
    unset($count_by_user_then_status["TOTAL"]);
    $count_by_user_then_status["TOTAL"] = $total_row;

    $params["user_status_count"] = $count_by_user_then_status;

    return $params;
}


/**
 * @param string        $mode     Name of the format mode
 * @param Doku_Renderer $renderer ref to the Doku_Renderer
 * @param array         $params   Parameter object returned by handle()
 */
function renderActivityByUserReport(/** @noinspection PhpUnusedParameterInspection */
    $mode, Doku_Renderer $renderer, $params)
{
    global $CHUNKPROGRESS_STATUS_TAGS;

    $renderer->table_open();

    $renderer->tablerow_open();

    $renderer->tablecell_open();
    $renderer->strong_open();
    $renderer->unformatted("User");
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

    $user_status_count = $params["user_status_count"];
    foreach ($user_status_count as $user => $statuses) {
        $renderer->tablerow_open();
        $renderer->tablecell_open();
        $renderer->unformatted($user);
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

