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
 * Convenience function to validate the end date.
 *
 * @param array $params The parameters given by the user
 *
 * @return array An updated $params array with data filled in
 */
function validateEndDate($params) {
    // Validate end_date
    if ($params["end_date"] == "") {
        $params["end_date"] = date("Y-m-d");
    }
    $end_timestamp = strtotime($params["end_date"]);
    if ($end_timestamp == false) {
        $params["message"]
            = "ERROR: end_date: Couldn't understand '"
            . $params["end_date"]."' as a date.";
    }
    $params["end_timestamp"] = $end_timestamp;
    return $params;
}


/**
 * Convenience function to validate the start date.
 *
 * @param array $params The parameters given by the user
 *
 * @return array An updated $params array with data filled in
 */
function validateStartDate($params) {
    if ($params["start_date"] == "") {
        $params["start_date"] = "1970-01-01";
    }
    $start_timestamp = strtotime($params["start_date"]);
    if ($start_timestamp == false) {
        $params["message"]
            = "ERROR: start_date: Couldn't understand '"
            . $params["start_date"]."' as a date.";
    }
    $params["start_timestamp"] = $start_timestamp;
    return $params;
}


/**
 * Convenience function to validate the namespace.
 *
 * @param array $params The parameters given by the user
 *
 * @return array An updated $params array with data filled in
 */
function validateNamespace($params) {
    if ($params["namespace"] == "") {
        $params["message"]
            = "ERROR: Please specify the namespace, e.g. namespace=en:bible:notes";
    }
    return $params;
}


/**
 * Extract parameters from plugin syntax match.  If any problems occurred,
 * the "message" attribute of the returned array will be set with an array
 * of strings containing the messages.
 *
 * @param string $match           The text matched by the plugin pattern
 * @param array  $expected_params The parameters the plugin is expecting
 *
 * @return array An array of found parameters.
 */
function getParams($match, $expected_params) {
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
            = "EXCEPTION: Please tell a developer about this: " . $exception->getMessage();
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
 * @return mixed The (numeric) date the page was modified
 */
function getPageTimestamp($page_id, $revision_id) {
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
 * @return string The name of the user responsible for the revision, or "" if the user
 * could not be determined
 */
function getPageUser($page_id, $revision_id) {
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
 * @return array An array containing the tags found (the array may be empty but will
 * not be null)
 */
function getTags($page_id, $revision_id) {
    $tags = array();
    $filename = wikiFN($page_id, $revision_id);
    if (file_exists($filename)) {
        $lines = gzfile($filename);
        foreach ($lines as $line) {
            $matches = array();
            preg_match_all("/{{tag>([^}]*)}}/", strtolower($line), $matches);
            // $matches[1] contains all instances of the the space-separated tags
            foreach ($matches[1] as $match) {
                $tags = array_merge($tags, explode(" ", $match));
            }
        }
    } else {
        error_log(
            "chunkprogress:Utils.php#getTags():" .
            " Warning: file does not exist ($filename)"
        );
    }
    return $tags;
}


/**
 * Extract status tags from tag array.
 *
 * @param array $tags Array of tags
 *
 * @return array An array containing the status tags found (the array may be empty
 * but will not be null)
 */
function getStatusFromTags($tags) {
    global $CHUNKPROGRESS_STATUS_TAGS;
    return array_intersect($CHUNKPROGRESS_STATUS_TAGS, $tags);
}


/**
 * Convenience function to get just the status tags from a page revision.
 *
 * @param string $page_id     The page ID
 * @param string $revision_id The revision ID, or "" if current revision
 *
 * @return array An array containing the status tags found (the array may be empty
 * but will not be null)
 */
function getStatusTags($page_id, $revision_id) {
    return getStatusFromTags(getTags($page_id, $revision_id));
}

/**
 * Returns all statuses for all revisions of a given page
 *
 * {
 *   "111111": ["check"],
 *   "222222": ["check"],
 *   "333333": ["review"],
 *   "444444": ["review", "discuss"],
 *   "555555": ["publish"]
 * }
 *
 * @param string $page_id The page to read revisions from
 *
 * @return an array containing the page status revisions
 */
function getStatusesForPage($page_id) {
    $statuses_by_revision = array();

    // Get all revisions for this page.
    $revision_ids = getRevisions(
        $page_id,
        0,
        10000
    );

    // Reverse the array so that it goes least-recent to most-recent
    $revision_ids = array_reverse($revision_ids);

    // Push the current revision onto the stack.
    array_push($revision_ids, "");

    // Extract status from each revision
    foreach ($revision_ids as $revision_id) {
        $status_tags = getStatusTags($page_id, $revision_id);
        $statuses_by_revision[$revision_id] = $status_tags;
    }

    return $statuses_by_revision;
}

/**
 * Returns all statuses for all revisions of a given page
 *
 * {
 *   "first_to_check": 11111,
 *   "first_to_review": 22222,
 *   "first_to_publish": 33333
 * }
 *
 * If the page has no statuses, the entry will be set to null.  For
 * example, if the page had no review revisions, then:
 *
 * {
 *   "first_to_check": 11111,
 *   "first_to_review": null,
 *   "first_to_publish": 33333
 * }
 *
 * @param string $page_id The page to read revisions from
 *
 * @return array An associative array containing the breakpoints
 */
function getStatusBreakpointsForPage($page_id) {
    // Get statuses for each revision of the page
    $statuses_by_revision = getStatusesForPage($page_id);

    // Search revision statuses for breakpoints
    $previous_status = null;
    $first_to_check = "(none)";
    $first_to_review = "(none)";
    $first_to_publish = "(none)";
    foreach (array_keys($statuses_by_revision) as $revision_id) {

        // Get status tags.  We only care about the leftmost status
        $status_tags = $statuses_by_revision[$revision_id];
        $status_tag_values = array_values($status_tags);
        $status = array_shift($status_tag_values);

        // error_log(
        //     "Page: " . $ID .
        //     " Revision: " . $revision_id .
        //     " Status: " . $status
        // );

        // Look for the first breakpoint for each status.
        if ($previous_status != "(none)") {
            if ($first_to_check == "(none)"
                and $previous_status != "check"
                and $status == "check"
            ) {
                $first_to_check = $revision_id;
            }
            if ($first_to_review == "(none)"
                and $previous_status != "review"
                and $status == "review"
            ) {
                $first_to_review = $revision_id;
            }
            if ($first_to_publish == "(none)"
                and $previous_status != "publish"
                and $status == "publish"
            ) {
                $first_to_publish = $revision_id;
            }
        }

        $previous_status = $status;
    }

    // error_log(
    //     "Page: " . $page_id .
    //     " First to check: " . $first_to_check
    // );
    // error_log(
    //     "Page: " . $page_id .
    //     " First to review: " . $first_to_review
    // );
    // error_log(
    //     "Page: " . $page_id .
    //     " First to publish: " . $first_to_publish
    // );

    return array(
        'first_to_check' => $first_to_check,
        'first_to_review' => $first_to_review,
        'first_to_publish' => $first_to_publish
    );
}

/**
 * Returns Dokuwiki text with links to breakpoint statuses for page
 *
 * @param string $page_id The page to read revisions from
 *
 * @return string A Dokuwiki-formatted string with links to the diffs
 */
function generateDiffLinks($page_id) {

    // error_log("----- generateDiffLinks($page_id)");

    $link_text = "";

    $breakpoints = getStatusBreakpointsForPage($page_id);
    // error_log("breakpoints: Array size " . count($breakpoints));
    $first_to_check = $breakpoints["first_to_check"];
    $first_to_review = $breakpoints["first_to_review"];
    $first_to_publish = $breakpoints["first_to_publish"];
    // error_log("first_to_check: $first_to_check");
    // error_log("first_to_review: $first_to_review");
    // error_log("first_to_publish: $first_to_publish");

    // Check -> Review
    if ($first_to_check != "(none)" and $first_to_review != "(none)") {
        if ($link_text != "") {
            $link_text = $link_text . " | ";
        }
        $link_text = $link_text .
            " [[$page_id?do=diff&rev2%5B0%5D="
            . $first_to_check
            . "&rev2%5B1%5D="
            . $first_to_review
            . "&difftype=sidebyside|check-review]]";
    }

    // Check -> Publish
    if ($first_to_check != "(none)" and $first_to_publish != "(none)") {
        if ($link_text != "") {
            $link_text = $link_text . " | ";
        }
        $link_text = $link_text .
            " [[$page_id?do=diff&rev2%5B0%5D="
            . $first_to_check
            . "&rev2%5B1%5D="
            . $first_to_publish
            . "&difftype=sidebyside|check-publish]]";
    }

    // Review -> Publish
    if ($first_to_review != "(none)" and $first_to_publish != "(none)") {
        if ($link_text != "") {
            $link_text = $link_text . " | ";
        }
        $link_text = $link_text .
            " [[$page_id?do=diff&rev2%5B0%5D="
            . $first_to_review
            . "&rev2%5B1%5D="
            . $first_to_publish
            . "&difftype=sidebyside|review-publish]]";
    }

    // Publish -> Current
    if ($first_to_publish != "(none)" && $first_to_publish != "") {
        if ($link_text != "") {
            $link_text = $link_text . " | ";
        }
        $link_text = $link_text .
            " [[$page_id?do=diff&rev2%5B0%5D="
            . $first_to_publish
            . "&rev2%5B1%5D="
            . "&difftype=sidebyside|publish-current]]";
    }

    // error_log("link_text: $link_text");

    return $link_text;
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
 * @return array An array containing the details of each page in the namespace
 */
function getAllPagesInNamespace($namespace, $depth=0) {
    // Find all pages under namespace
    global $conf;
    $data = array();
    $opts = array("depth" => $depth);
    $dir = str_replace(":", DIRECTORY_SEPARATOR, $namespace);
    $datadir = $conf["datadir"] . DIRECTORY_SEPARATOR . $dir;
    search($data, $datadir, 'search_allpages', $opts);
    // Replace each id with its full namespace
    foreach ($data as $key => $value) {
        $value["id"] = $namespace . ":" . $value["id"];
        $data[$key] = $value;
    }
    return $data;
}

/**
 * Debug function to echo out an array
 *
 * @param array  $array  the array to print
 * @param string $title  optional title to print above the array
 * @param int    $indent optional number of spaces to indent
 */
function debugEchoArray($array, $title="(array)", $indent=0) {
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

