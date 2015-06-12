<?php
/**
 * Name: ajax_base.php
 * Description: A base class for the syntax plugins.
 *
 * Author: Phil Hopper
 * Date:   2015-05-23
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


class Door43_Ajax_Helper {

    /**
     * @var Door43_Ajax_Helper
     */
    private static $instance;
    private $handlers = array();

    /**
     * @param $controller Doku_Event_Handler
     */
    private function __construct($controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax_call');
    }

    /**
     * @param $controller
     * @param $requestName
     * @param array [object, string] $classInstanceAndMethod An array, the first item is the class instance, the second
     *              item is the name of the callback function. The $event variable is passed as the only parameter.
     */
    public static function register_handler($controller, $requestName, $classInstanceAndMethod) {

        if (empty(self::$instance)) self::$instance = new Door43_Ajax_Helper($controller);

        self::$instance->handlers[$requestName] = $classInstanceAndMethod;
    }

    /**
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_ajax_call(Doku_Event &$event, $param) {

        // do we have a handler defined for this call?
        if (empty($this->handlers[$event->data])) return;

        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        $handler = $this->handlers[$event->data];

        $handler[0]->$handler[1]($event);
    }
}
