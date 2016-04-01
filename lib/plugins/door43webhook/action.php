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


class action_plugin_door43webhook extends DokuWiki_Action_Plugin {

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
            case 'door43webhook-bible':
                $this->_bible();
                exit;
                break;
            default:
                break;
        }
    }

    private function _bible() {
        $payload = json_decode(@file_get_contents('php://input'));

        if ($payload){
            $repo = $payload->repository->name;
            $resource = explode('-',$repo)[0];

            $command = "cd /var/www/vhosts/door43.org/$repo && git reset --hard HEAD && git pull";
            print $command."\n";
            print shell_exec($command)."\n";

            $files = array();
            foreach($payload->commits as $commit){
                $files = array_merge($files, $commit->modified);
                $files = array_merge($files, $commit->added);
            }
            foreach($files as $file){
                $file_parts = explode('/', $file);
                if(count($file_parts) == 2){
                    $book = explode('-', $file_parts[0])[1];
                    $chapter = explode('.',$file_parts[1])[0];
                    print_r(array('repo'=>$repo, 'resource'=>$resource, 'book'=>$book));
                    $command = "/var/www/vhosts/door43.org/tools/uwb/make_book_from_chapters.py -v draft -r $resource -b $book";
                    print $command."\n";
                    print shell_exec($command)."\n";
                    $command = "/var/www/vhosts/door43.org/tools/uwb/put_chunks_into_notes.py -b $book -c $chapter";
                    print $command."\n";
                    print shell_exec($command)."\n";
                }
            }
        }
        else {
            print "No payload!!";
        }
    }
}

// vim:ts=4:sw=4:et:
