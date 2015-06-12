<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Yvonne Lu <yvonne@leapinglaptop.com>
 * 
 * 
 * 
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_nsmsg extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo() {
        return array(
                'author' => 'Yvonne Lu',
                'email'  => '<yvonne@leapinglaptop.com>',
                'date'   => @file_get_contents(DOKU_PLUGIN.'nsmsg/VERSION'),
                'name'   => 'namespace message Plugin',
                'url'   => 'https://github.com/Door43/nsmsg',
                );
    }

    /**
     * Constructor
     */
    function action_plugin_message() {
      $this->setupLocale();
    }
                              
    /**
     * register the eventhandlers
     */
    function register(&$contr) {
        $contr->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_display_nsmsg', array());
    }

    function _display_nsmsg(&$event, $param) {
        global $conf;
        global $INFO;
        
        
        $file=$conf['cachedir'].'/nsmsg.xml';
        if (file_exists($file) && filesize($file)) {
             try { 
                $xml=simplexml_load_file($file);
                foreach ($xml as $message) {
                    if (strcmp(trim($INFO['namespace']), trim($message->namespace)) == 0) {
                        //display the message with link
                        $display="<a href='".$message->link."' target='_blank'>".$message->body."</a>";
                        msg($display, intval($message->type));   
                        
                    }
                }
             }catch (Exception $e) { 
                echo $this->getLang('error_c'); 
            } 
        }
      
        return;
    }
}

