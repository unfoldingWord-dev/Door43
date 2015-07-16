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
        $params = array(
            "page" => "(no page)"
        );
        return $params;
    }

    function render($mode, &$renderer, $params) {
        $renderer->table_open();
        $renderer->tablerow_open();
        $renderer->tablecell_open();
        $renderer->strong_open();
        $renderer->unformatted("Key");
        $renderer->strong_close();
        $renderer->tablecell_close();
        $renderer->tablecell_open();
        $renderer->strong_open();
        $renderer->unformatted("Value");
        $renderer->strong_close();
        $renderer->tablerow_close();
        $renderer->tablecell_close();
        foreach ($params as $key => $value) {
            $renderer->tablerow_open();
            $renderer->tablecell_open();
            $renderer->unformatted($key);
            $renderer->tablecell_close();
            $renderer->tablecell_open();
            $renderer->unformatted($value);
            $renderer->tablecell_close();
            $renderer->tablerow_close();
        }
        $renderer->table_close();
    }

}

