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
    $namespace = $params["namespace"];
    if ($namespace == "") {
        $params["message"]
            = "ERROR: Please specify the namespace, e.g. namespace=en:bible:notes";
        return $params;
    }
    $data = getAllPagesInNamespace($namespace);
    //debugEchoArray($data, "Pages");

    $params["report_title"] = "Activity by User";

    return $params;
}


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
            debugEchoArray($value, "(array)", $indent+4);
        } else {
            echo $indent_str . $key . ": " . $value . "<br/>";
        }
    }
    echo "<br/>";
}




// vim: foldmethod=indent
