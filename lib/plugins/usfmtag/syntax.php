<?php
/**
 * USFMTag plugin for DokuWiki.
 *
 * @license GPL 3 (http://www.gnu.org/licenses/gpl.html) - NOTE: USFMTag
 * @author Originally developed for MediaWiki by Rusmin Soetjipto, 
 * ported by Yvonne Lu <yvonnel@leapinglaptop.com>
 * 
 * 
 * 1/30/14 added to lexer so that the UFSM tags can be in either all upper or all lower cases
 */



if (!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once (DOKU_PLUGIN . 'syntax.php');
require_once (DOKU_PLUGIN . 'usfmtag/UsfmParagraphState.php');
require_once (DOKU_PLUGIN . 'usfmtag/UsfmText.php');
require_once (DOKU_PLUGIN . 'usfmtag/UsfmTagDecoder.php');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_usfmtag extends DokuWiki_Syntax_Plugin {

   

   
    function getType(){
        return 'protected';
    }
    
    function getPType() {
        return 'block';
    }
	
   

   
    function getSort(){
        return 68;
    }


   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    * <USFM>(?=.*</USFM>)
    */
    function connectTo($mode) {
      $this->Lexer->addEntryPattern('<USFM>(?=.*</USFM>)', $mode, 'plugin_usfmtag');
      $this->Lexer->addEntryPattern('<usfm>(?=.*</usfm>)', $mode, 'plugin_usfmtag');
      
    }
    /*
     * </USFM>
     */
     function postConnect() {
        $this->Lexer->addExitPattern('</USFM>', 'plugin_usfmtag');
        $this->Lexer->addExitPattern('</usfm>', 'plugin_usfmtag');
    }
	



   /**
    * Handler to prepare matched data for the rendering process.
    *
    * <p>
    * The <tt>$aState</tt> parameter gives the type of pattern
    * which triggered the call to this method:
    * </p>
    * <dl>
    * <dt>DOKU_LEXER_ENTER</dt>
    * <dd>a pattern set by <tt>addEntryPattern()</tt></dd>
    * <dt>DOKU_LEXER_MATCHED</dt>
    * <dd>a pattern set by <tt>addPattern()</tt></dd>
    * <dt>DOKU_LEXER_EXIT</dt>
    * <dd> a pattern set by <tt>addExitPattern()</tt></dd>
    * <dt>DOKU_LEXER_SPECIAL</dt>
    * <dd>a pattern set by <tt>addSpecialPattern()</tt></dd>
    * <dt>DOKU_LEXER_UNMATCHED</dt>
    * <dd>ordinary text encountered within the plugin's syntax mode
    * which doesn't match any pattern.</dd>
    * </dl>
    * @param $aMatch String The text matched by the patterns.
    * @param $aState Integer The lexer state for the match.
    * @param $aPos Integer The character position of the matched text.
    * @param $aHandler Object Reference to the Doku_Handler object.
    * @return Integer The current lexer state for the match.
    * @public
    * @see render()
    * @static
    */
    /*
    function handle($match, $state, $pos, &$handler){
        switch ($state) {
          case DOKU_LEXER_ENTER : 
            break;
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            break;
          case DOKU_LEXER_EXIT :
            break;
          case DOKU_LEXER_SPECIAL :
            break;
        }
        return array();
    }*/
    
    function handle($match, $state, $pos, &$handler) {
        switch ($state) {
            case DOKU_LEXER_ENTER :      return array($state, '');
            case DOKU_LEXER_UNMATCHED :  {
                $tmp = new UsfmTagDecoder();
                return array($state, $tmp->decode($match));
            }                                                                  
            case DOKU_LEXER_EXIT :       return array($state, '');
        }
        return array($state,'');
    }

   /**
    * Handle the actual output creation.
    *
    * <p>
    * The method checks for the given <tt>$aFormat</tt> and returns
    * <tt>FALSE</tt> when a format isn't supported. <tt>$aRenderer</tt>
    * contains a reference to the renderer object which is currently
    * handling the rendering. The contents of <tt>$aData</tt> is the
    * return value of the <tt>handle()</tt> method.
    * </p>
    * @param $aFormat String The output format to generate.
    * @param $aRenderer Object A reference to the renderer object.
    * @param $aData Array The data created by the <tt>handle()</tt>
    * method.
    * @return Boolean <tt>TRUE</tt> if rendered successfully, or
    * <tt>FALSE</tt> otherwise.
    * @public
    * @see handle()
    */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            list($state,$match) = $data;
            switch ($state) {
                case DOKU_LEXER_UNMATCHED :  $renderer->doc .= $match; break;
                //_xmlEntities strips out html so can't use it.
                //case DOKU_LEXER_UNMATCHED :  $renderer->doc .= $renderer->_xmlEntities($match); break;
            }
            return true;
        }
        return false;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
?>