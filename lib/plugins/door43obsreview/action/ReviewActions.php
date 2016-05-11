<?php
/**
 * Name: ReviewActions.php
 * Description: A Dokuwiki plugin to attach checking and review functionality to OBS pages
 *
 * Author: Phil Hopper
 * Date:   2015-04-06
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_door43obsreview_ReviewActions extends DokuWiki_Action_Plugin {

    /**
     * @var door43Cache
     */
    private $cache;

    /**
     * Registers a callback functions for the desired actions
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

        // hook before_render to insert code to display checking level
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_register_action');
    }

    /**
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_register_action(Doku_Event &$event, /** @noinspection PhpUnusedParameterInspection */ $param) {

        if ($event->data !== 'show') return;

        global $INFO;

        $parts = explode(':', strtolower($INFO['id']));

        // If this is an OBS request, the id will have these parts:
        // [0] = language code / namespace
        // [1] = 'obs'
        // [2] = story number '01' - '50'
        if (count($parts) < 2) return;
        if ($parts[1] !== 'obs') return;
        if (isset($parts[2]) && (preg_match('/^[0-9][0-9]$/', $parts[2]) !== 1)) return;

        // put the checking level badge on this page
        $status = $this->get_level_status_from_cache($parts[0]);

        // get the css class
        if (count($parts) == 2) {

            // on this page we need to leave room for the table of contents
            $cssClass = 'obs-checking toc';
        }
        else {

            // on this page we have the fill width
            $cssClass = 'obs-checking';
        }

        $requestToPublishFormUrl = '<a href="http://ufw.io/pub" target="_blank">'.$this->getLang('requestToPublish').'</a>';

        if (empty($status)) {
            $text = sprintf($this->getLang('noCheckingLevelSummary'), $requestToPublishFormUrl);
        }
        else {
            $url = '<a href="https://unfoldingword.org/stories/">https://unfoldingword.org/stories</a>';
            $text = sprintf($this->getLang('checkingLevelSummary'), $status['checking_level'], $status['version'], $url);
            $cssClass .= ' obs-checked level-' . $status['checking_level'];

            // TODO: find an icon to use as the link to click on
            if (isset($parts[2]) && $status['checking_level'] < 3)
                $text .= ' '.sprintf($this->getLang('checkingLevelUpdate'), $status['checking_level']+1, $requestToPublishFormUrl);
        }

        echo '<div class="' . $cssClass . '"><p style="font-size: 0.875em; color: #666; max-height: 30px;">' . $text . '</p></div><br /><br />';

    }

    /**
     * Gets the OBS status of the requested language
     * @param $langCode
     * @return mixed   Returns the status block if successful, otherwise returns null
     */
    private function get_level_status_from_cache($langCode) {

        /* @var $cache door43Cache */
        $cache = $this->getCache();
        $cacheFile = 'obs-catalog.json';
        $levels = $cache->getObject($cacheFile, true);

        // download from api.unfoldingWord.org if needed
        if (empty($levels)) {

            $http = new DokuHTTPClient();
            $raw = $http->get('https://api.unfoldingword.org/obs/txt/1/obs-catalog.json');
            $levels = json_decode($raw, true);
            $cache->saveString($cacheFile, $raw);
        }

        // return null if there are still no levels
        if (empty($levels)) return null;

        foreach($levels as $level) {

            if ($level['language'] == $langCode) {
                if (isset($level['status'])) return $level['status'];
            }
        }

        return null;
    }

    private function getCache() {

        if (empty($this->cache)) {

            /* @var $door43shared helper_plugin_door43shared */
            global $door43shared;

            if (empty($door43shared)) {
                $door43shared = plugin_load('helper', 'door43shared');
            }

            $this->cache = $door43shared->getCache();
        }

        return $this->cache;
    }
}
