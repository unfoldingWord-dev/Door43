<?php
/**
 * Name: MissingOverride.php
 * Description: A Dokuwiki action plugin to override the default behavior if OBS is missing for the namespace.
 *
 * Author: Phil Hopper
 * Date:   2015-03-04
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_door43obs_MissingOverride extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_obs_action');
    }

    /**
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_obs_action(Doku_Event &$event, /** @noinspection PhpUnusedParameterInspection */ $param) {

        if ($event->data !== 'show') return;

        global $INFO;

        $parts = explode(':', $INFO['id']);
        if ((count($parts) == 2) && ($parts[1] == 'obs')) {

            if ((!empty($INFO['filepath'])) && (!is_file($INFO['filepath']))) {

                // if you are here, obs has not yet been configured in this namespace, so redirect to the setup page
                send_redirect(DOKU_URL . 'obs-setup');
            }
        }
    }
}
