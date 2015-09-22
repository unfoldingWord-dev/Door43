<?php
/**
 * Clean up code
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     David Stone <david@nnucomputerwhiz.com>
 */

/**
 * Class action_plugin_changes
 */
class action_plugin_codecleanup extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'cleanupCode');
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function cleanupCode(Doku_Event &$event, $param) {
        
        if($this->startsWith($event->data[1],'en:bible:notes') ||
           $this->startsWith($event->data[1],'en:obe') ||
           $this->startsWith($event->data[1],'playground')) {
            
            $event->data[0][1] = preg_replace('/<\/?font[^>]*>/', '', $event->data[0][1]);
        }

    }
    
    private function startsWith($haystack, $needle) {
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }
            

}
