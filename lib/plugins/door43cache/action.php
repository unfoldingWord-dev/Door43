<?php
/**
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}
require_once(DOKU_PLUGIN . 'action.php');


class action_plugin_door43cache extends DokuWiki_Action_Plugin {

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_tpl_act', array());
    }

    function handle_tpl_act(&$event, $param) {
        global $INPUT, $ID;

        $do = $event->data;
        if (is_array($do)) {
            list($do) = array_keys($do);
        }

        switch ($do) {
            case 'cleardoor43cache':
                shell_exec("rm data/cache/door43_cache/*");
                msg("Cache cleared", 1);
                $event->data = "show";
                return;
            default:
                break;
        }
    }
}
