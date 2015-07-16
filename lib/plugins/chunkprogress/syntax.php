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
            "page" => "",
        );


        // Extract parameter string from match
        $begin_pos = strpos($match, ">") + 1;
        $end_pos = strpos($match, "}") - 1;
        $all_params_string = substr($match, $begin_pos, $end_pos - $begin_pos + 1);
        // Process parameters
        try {
            foreach(explode("&", $all_params_string) as $param_string) {
                $param_pair = explode("=", $param_string);
                if (count($param_pair) == 2) {
                    $key = $param_pair[0];
                    $value = $param_pair[1];
                    if (array_key_exists($key, $params)) {
                        $params[$key] = $value;
                    } else {
                        $params["message"] .= "\nWARNING: didn't recognize parameter '" . $key . "' (maybe you misspelled it?)";
                    }
                } else {
                    $params["message"] .= "\nWARNING: didn't understand parameter '" . $param_string . "' (maybe you forgot the '=''?)";
                }
            }
        } catch (Exception $exception) {
            $params["message"] .= "EXCEPTION: Please tell a developer about this: " . $e->getMessage();
        }

        return $params;
    }

    function render($mode, &$renderer, $params) {
        // Print warnings or errors, if any
        if (array_key_exists("message", $params)) {
            $renderer->strong_open();
            $renderer->unformatted($params["message"]);
            $renderer->strong_close();
        }
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

