<?php

// Must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');


class syntax_plugin_chunkprogress extends DokuWiki_Syntax_Plugin {

    function getInfo() { 
        return confToHash(dirname(__FILE__).'/plugin.info.txt'); 
    }

    function getType() {
        return "substition";
    }

    function getPType() {
        return "block";
    }

    function getSort() {
        return 1;
    }

    function connectTo($mode) {
       $this->Lexer->addSpecialPattern('\{\{chunkprogress>[^}]*\}\}',$mode,'plugin_chunkprogress');
    }


    function handle($match, $state, $pos, &$handler){
        return "Hello world!2";
    }

    function render($mode, &$renderer, $data) {
        $renderer->unformatted($data);
    }

}

