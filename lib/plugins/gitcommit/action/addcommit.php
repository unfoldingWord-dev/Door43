<?php
/**
 * DokuWiki Plugin gitcommit (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Dave Pearce <dave@distantshores.org>
 * Updates  Jesse Griffin <jesse@distantshores.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_gitcommit_addcommit extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

       $controller->register_hook('IO_WIKIPAGE_WRITE', 'AFTER', $this, 'handle_io_wikipage_write');
   
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_io_wikipage_write(Doku_Event &$event, $param) {
        if (empty($event->data[3])) {
            global $USERINFO;
            $modified_file = $event->data[0][0];
            $pageName = $event->data[2];
            $pageContent = $event->data[0][1];

            // get the summary directly from the form input
            // as the metadata hasn't updated yet
            $editSummary = $GLOBALS['INPUT']->str('summary');

            // empty content indicates a page deletion
            if ($pageContent == '') {
                $actionType = 'Page Delete';

                // bad hack as DokuWiki deletes the file after this event
                // thus, let's delete the file by ourselves, so git can recognize the deletion
                // DokuWiki uses @unlink as well, so no error should be thrown if we delete it twice
                @unlink($modified_file);

            } else {
                $actionType = 'Page Edit';
            }

            $commit_message = sprintf("\"%s [%s]: %s [%s]\"",
                $actionType, $pageName, $editSummary,$USERINFO['name']);

            $debug = $keyvalue = $this->getConf('debug');
            
            
            $curr_dir = getcwd();             // Save where I am now
            $dirname = dirname($modified_file);  // The dir or the file that was changed
            $basename = basename($modified_file);  // The filename of the file that was changed
            chdir($dirname);  // Change to the folder where the file changed
            
            if ($debug) {
                msg("AllowDebug " . $debug);
                msg("Filename " . $modified_file);
                msg("Currdir " . getcwd());
                msg("Basename " . $basename);
                msg("Event <pre>" . print_r($event, TRUE) . "</pre>");
                msg("Param <pre>" . print_r($param, TRUE) . "</pre>");
                msg("Userinfo <pre>" . print_r($USERINFO['name'], TRUE) . "</pre>");
                msg("Commit msg <pre>" . print_r($commit_message, TRUE) . "</pre>");
            }

            
            $output = array();
            exec("/usr/bin/git add " . $basename, $output, $rc);
            if ($debug) {
                msg("Git add output [" . $rc . "] <pre>" . print_r($output, TRUE) . "</pre>");
            }

            $output = array();
            exec("/usr/bin/git commit " . $basename . " -m " . $commit_message, $output, $rc);
            if ($debug) {
                msg("Git commit output [" . $rc . "] <pre>" . print_r($output, TRUE) . "</pre>");
            }            

            $output = array();
            exec("/usr/bin/git pull --no-edit origin master", $output, $rc);
            if ($debug) {
                msg("git pull output [" . $rc . "] <pre>" . print_r($output, TRUE) . "</pre>");
            }
            
            $output = array();
            exec("/usr/bin/git push origin master", $output, $rc);
            if ($debug) {
                msg("git push output [" . $rc . "] <pre>" . print_r($output, TRUE) . "</pre>");
                }
            
            chdir($curr_dir);                                                    // Change back to where we were
        }
        // msg("Event <pre>" . print_r($event, TRUE) . "</pre>");
        // msg("Param <pre>" . print_r($param, TRUE) . "</pre>");
    }

}

// vim:ts=4:sw=4:et:
