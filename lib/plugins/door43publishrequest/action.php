<?php
/**
 * 
 * 
 * @author  Abi Gundy <abigundy@gmail.com>
 * @date 11-18-2015
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_door43publishrequest extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

       $controller->register_hook('TEMPLATE_SITETOOLS_DISPLAY', 'BEFORE' , $this, 'handle_template_sitetools_display');
   
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_template_sitetools_display(/** @noinspection PhpUnusedParameterInspection */
        Doku_Event &$event,
        $param) {
        
        //$url = 'http://td.unfoldingword.org/publishing/publish/request/';
        $url = 'https://docs.google.com/a/sil.org/forms/d/1WWbIZkDT0-mwr1LzYdnxZq2S4gzMg7PbxL8MjFReHFE/viewform';
        $out = '<a href="'.$url.'"><li>'.$this->getLang('publishRequest').'</li></a>';
        echo $out;

    }
}

