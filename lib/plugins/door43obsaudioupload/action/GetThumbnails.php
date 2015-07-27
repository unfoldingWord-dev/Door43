<?php
/**
 * Name: GetThumbnails.php
 * Description:
 *
 * Author: Phil Hopper
 * Date:   2015-02-18
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_door43obsaudioupload_GetThumbnails extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_do_action');
    }

    /**
     * Gets the thumbnail
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_do_action(Doku_Event &$event, $param) {

        if ($event->data !== 'obsaudioupload_frame_thumbnail') return;

        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        // get the url of the image to make into a thumbnail
        $url = $GLOBALS['INPUT']->str('img');
        $imageInfo = getimagesize($url); // 0=width, 1=height, 2=format

        if ($imageInfo[2] == IMG_PNG)
            $img = imagecreatefrompng($url);
        else
            $img = imagecreatefromjpeg($url);

        $newHeight = 50;
        $newWidth = (int)($imageInfo[0] * $newHeight / $imageInfo[1]);

        $newImg = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresized($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $imageInfo[0], $imageInfo[1]);

        // output
        header('Content-Type: application/jpeg');
        imagejpeg($newImg, null, 60);

        exit();
    }
}
