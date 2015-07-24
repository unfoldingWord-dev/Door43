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



// vim: foldmethod=indent
