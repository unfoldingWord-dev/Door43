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
                    $params["message"] .=
                        "<br/>WARNING: didn't recognize parameter '" .
                        $key . "' (maybe you misspelled it?)";
                }
            } else {
                // We didn't find both a key and a value
                $params["message"] .=
                    "<br/>WARNING: didn't understand parameter '" .
                    $param_string . "' (maybe you forgot the '=''?)";
            }
        }
    } catch (Exception $exception) {
        $params["message"] .=
            "EXCEPTION: Please tell a developer about this: " . $e->getMessage();
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





// vim: foldmethod=indent
