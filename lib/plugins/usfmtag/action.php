<?php
/**
 * DokuWiki Plugin UsfmTag (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Yvonne Lu <yvonnel@leapinglaptop.com>
 * 
 * 3-18-15
 * Added fix so that it will work with the Discussion plugin
 * 
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_usfmtag extends DokuWiki_Action_Plugin {

   function register(&$controller) {
      $controller->register_hook('PARSER_WIKITEXT_PREPROCESS',
'BEFORE', $this, 'handle_parser_wikitext_preprocess');
   }

   function handle_parser_wikitext_preprocess(&$event, $param) {
       global $ID;
       
       if(substr($ID,-5) != '.usfm') return true;
       
        //see if this is submitted with a discussion window
        $discussion = $_REQUEST['comment'];

        if (!$discussion) {
            //added infile to mark that this file has usfm extention
            $event->data = "<USFM>\n".$event->data."\n</USFM>";

            //get discussion plugin to work with usfm file

            $pattern = '/~~DISCUSSION.*~~/';

            $event->data = preg_replace($pattern, "</USFM>$0<USFM>", $event->data);
        }

        //echo "action:  ".$event->data."<br>";
            
            
            
            
            
           
            
            
   }

}