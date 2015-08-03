<?php

/**
 * PHP version 5
 *
 * Creates a progress report for a given chunk
 *
 * @category Door43
 * @package  Chunkprogress
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */

/* Array of all possible status tags, in the order they usually occur. */
global $CHUNKPROGRESS_STATUS_TAGS;
$CHUNKPROGRESS_STATUS_TAGS = array(
    "draft", "check", "review", "text", "discuss", "publish");


/**
 * Debug function to echo out an array
 *
 * @param array $array  the array to print
 * @param array $title  optional title to print above the array
 * @param array $indent optional number of spaces to indent
 *
 * @return Nothing
 */
function debugEchoArray($array, $title="(array)", $indent=0)
{
    $indent_str = str_repeat("&nbsp;", $indent);
    echo $indent_str . $title . "<br/>";
    echo $indent_str . "-------------------" . "<br/>";
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            debugEchoArray($value, $key . ": (array)", $indent+4);
        } else {
            echo $indent_str . $key . ": " . $value . "<br/>";
        }
    }
    echo "<br/>";
}


/**
 * Extract parameters from plugin syntax match.  If any problems occurred,
 * the "message" attribute of the returned array will be set with an array
 * of strings containing the messages.
 *
 * @param string $match           The text matched by the plugin pattern
 * @param array  $expected_params The parameters the plugin is expecting
 *
 * @return an array of found parameters.
 */
function getParams($match, $expected_params)
{
    // Make a copy of $expected_params
    $params = $expected_params;

    // Extract parameter string from match
    $begin_pos = strpos($match, ">") + 1;
    $end_pos = strpos($match, "}") - 1;
    $all_params_string = substr($match, $begin_pos, $end_pos - $begin_pos + 1);

    // Process parameters
    try {
        foreach (explode("&", $all_params_string) as $param_string) {
            $param_pair = explode("=", $param_string);
            if (count($param_pair) == 2) {
                // We found a key and a value
                $key = $param_pair[0];
                $value = $param_pair[1];
                if (array_key_exists($key, $params)) {
                    // This is an expected paramater, assign value
                    $params[$key] = $value;
                } else {
                    // We weren't expecting this parameter
                    $params["message"]
                        = "WARNING: didn't recognize parameter '" .
                        $key . "' (maybe you misspelled it?)";
                }
            } else {
                // We didn't find both a key and a value
                $params["message"]
                    = "WARNING: didn't understand parameter '" .
                    $param_string . "' (maybe you forgot the '=''?)";
            }
        }
    } catch (Exception $exception) {
        $params["message"]
            = "EXCEPTION: Please tell a developer about this: " . $e->getMessage();
    }

    // Explode users
    if ($params["users"] != "") {
        $params["users"] = explode(" ", $params["users"]);
    }

    // Done
    return $params;
}


/**
 * Pull timestamp from page metadata
 *
 * @param string $page_id     The page ID
 * @param string $revision_id The revision ID, or "" if current revision
 *
 * @return the (numeric) date the page was modified
 */
function getPageTimestamp($page_id, $revision_id)
{
    if ($revision_id == "") {
        // No revision id, return modification date of current page
        return p_get_metadata($page_id)["date"]["modified"];
    } else {
        // Return date of revision
        return getRevisionInfo($page_id, $revision_id)["date"];
    }
}


/**
 * Pull the user that modified the page from metadata
 *
 * @param string $page_id     The page ID
 * @param string $revision_id The revision ID, or "" if current revision
 *
 * @return the name of the user responsible for the revision, or "" if the user
 * could not be determined
 */
function getPageUser($page_id, $revision_id)
{
    if ($revision_id == "") {
        // Try to pull the user from the last_change metadata
        $metadata = p_get_metadata($page_id);
        if (array_key_exists("last_change", $metadata)) {
            return $metadata["last_change"]["user"];
        }
        // There's no data as to who updated this, return empty user
        return "";
    } else {
        // Return user associated with revision
        return getRevisionInfo($page_id, $revision_id)["user"];
    }
}


/**
 * Retrieve tags from the given revision.  A tag statment looks like:
 *
 * {{tag>tag1 tag2 tag3}}
 *
 * Although pages usually only have one tag statment, it is possible for a page
 * to have multiple statments, even on the same line.  Of course, it's possible
 * a page has no tags too.
 *
 * @param string $page_id     The page ID
 * @param string $revision_id The revision ID, or "" if current revision
 *
 * @return An array containing the tags found (the array may be empty but will
 * not be null)
 */
function getTags($page_id, $revision_id)
{
    $tags = array();
    $lines = gzfile(wikiFN($page_id, $revision_id));
    foreach ($lines as $line) {
        $matches = array();
        preg_match_all("/{{tag>([^}]*)}}/", strtolower($line), $matches);
        // $matches[1] contains all instances of the the space-separated tags
        foreach ($matches[1] as $match) {
            $tags = array_merge($tags, explode(" ", $match));
        }
    }
    return $tags;
}


/**
 * Extract status tags from tag array.
 *
 * @param array $tags Array of tags
 *
 * @return An array containing the status tags found (the array may be empty
 * but will not be null)
 */
function getStatusFromTags($tags)
{
    global $CHUNKPROGRESS_STATUS_TAGS;
    return array_intersect($CHUNKPROGRESS_STATUS_TAGS, $tags);
}


/**
 * Convenience function to get just the status tags from a page revision.
 *
 * @param string $page_id     The page ID
 * @param string $revision_id The revision ID, or "" if current revision
 *
 * @return An array containing the status tags found (the array may be empty
 * but will not be null)
 */
function getStatusTags($page_id, $revision_id)
{
    return getStatusFromTags(getTags($page_id, $revision_id));
}


/**
 * Convenience function to get all the current pages in the given namespace.
 * 
 * Each returned page entry has the following structure:
 *
 * id: en:bible:notes:1ch:01:01
 * rev: 1437760017
 * mtime: 1437760017
 * size: 1599
 *
 * @param array $namespace The namespace to search
 *
 * @return An array containing the details of each page in the namespace
 */
function getAllPagesInNamespace($namespace)
{
    // Find all pages under namespace
    global $conf;
    $data = array();
    $opts = array("depth" => 0);
    $dir = str_replace(":", DIRECTORY_SEPARATOR, $namespace);
    search($data, $conf["datadir"], search_allpages, $opts, $dir);
    return $data;
}


/**
 * Convenience function to get just the status tags from a page revision.
 *
 * @param array $params The parameters given by the user
 *
 * @return An updated $params array with data filled in
 */
function handleActivityByUserReport($params)
{
    global $cache_revinfo;


    // Validate namespace
    $namespace = $params["namespace"];
    if ($namespace == "") {
        $params["message"]
            = "ERROR: Please specify the namespace, e.g. namespace=en:bible:notes";
        return $params;
    }

    // Validate start_date
    $start_date = $params["start_date"];
    if ($start_date == "") {
        $start_date = "1970-01-01";
    }
    $start_timestamp = strtotime($start_date);
    if ($start_timestamp == false) {
        $params["message"]
            = "ERROR: start_date: Couldn't understand '".$start_date."' as a date.";
        return $params;
    }
    $params["start_timestamp"] = $start_timestamp;

    // Validate end_date
    $end_date = $params["end_date"];
    if ($end_date == "") {
        $end_date = date("Y-m-d");
    }
    $end_timestamp = strtotime($end_date);
    if ($end_timestamp == false) {
        $params["message"]
            = "ERROR: end_date: Couldn't understand '".$end_date."' as a date.";
        return $params;
    }
    $params["end_timestamp"] = $end_timestamp;


    $params["report_title"]
        = "Activity by User for " . $namespace
        . " from " . $start_date . " to " . $end_date;

    // Find all pages in the namespace
    $pages = getAllPagesInNamespace($namespace);
    $params["debug_num_pages_in_ns"] = count($pages);

    // Build an array of the last update by user by page
    $num_revisions = 0;
    $num_revisions_within_dates = 0;
    $num_revisions_with_matching_users = 0;
    $page_count = 0;
    $last_revision_by_user_by_page = array();
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

        // Count number of revisions for debugging
        $num_revisions += count($revision_ids);

        // Consider each revision
        $prev_status_tags = array();
        foreach ($revision_ids as $revision_id) {
            if ($revision_id < $start_timestamp or $revision_id > $end_timestamp) {
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
            $statuses = getStatusTags($page_id, $last_revision_id);
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
    $count_of_status_by_user = array();
    foreach ($last_status_by_user_by_page as $user => $page_ids) {
        foreach ($page_ids as $page_id => $last_status) {
            if (array_key_exists($user, $count_of_status_by_user) == false) {
                $count_of_status_by_user[$user] = array();
            }
            if (array_key_exists(
                $last_status, $count_of_status_by_user[$user]
            ) == false) {
                $count_of_status_by_user[$user][$last_status] = 0;
            }
            $count_of_status_by_user[$user][$last_status] += 1;
        }
    }
    $params["user_status_count"] = $count_of_status_by_user;

    return $params;
}


/**
 * Renders activity report to the page
 * @param string $mode     Name of the format mode
 * @param obj    $renderer ref to the Doku_Renderer
 * @param obj    $params   Parameter object returned by handle()
 * @return Nothing?
 */
function renderActivityByUserReport($mode, &$renderer, $params)
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


