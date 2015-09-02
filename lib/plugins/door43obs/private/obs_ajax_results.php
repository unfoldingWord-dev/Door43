<?php
/**
 * Name: obs_ajax_results.php
 * Description: The structure returned to the browser for create_obs_???? requests
 *
 * Author: Phil Hopper
 * Date:   2014-12-10
 */

/**
 * The structure returned to the browser for create_obs_???? requests
 */
class ObsAjaxResults {

    /**
     * @var string 'OK' if the method was successful, something else if it wasn't.
     */
    public $result;

    /**
     * @var string The message to display to the user as a result of this operation.
     */
    public $htmlMessage;

    /**
     * @param string $result 'OK' if the method was successful, something else if it wasn't.
     * @param string $msg The HTML message to display to the user as a result of this operation.
     */
    function __construct($result, $msg) {

        $this->result = $result;
        $this->htmlMessage = $msg;
    }
}
