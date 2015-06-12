<?php
/**
 * Allow creation of XHTML definition lists:
 * <dl>
 *   <dt>term</dt>
 *   <dd>definition</dd>
 * </dl>
 *
 * Syntax:
 *   ; term : definition
 *   ; term
 *   : definition
 *
 * As with other dokuwiki lists, each line must start with 2 spaces or a tab
 * Nested definition lists are not supported at this time
 *
 * This plugin is heavily based on the definitions plugin by Pavel Vitis which
 * in turn drew from the original definition list plugin by Stephane Chamberland.
 * A huge thanks to both of them.
 *
 * ODT support provided by Gabriel Birke <birke@d-scribe.de>
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Chris Smith <chris [at] jalakai [dot] co [dot] uk>
 *
 */

if (!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

// ---------- [ Settings ] -----------------------------------------

// define the trigger characters
//   ";" & ":" are the mediawiki settings.
//   "=" & ":" are the settings for the original plugin by Pavel
if (!defined('DL_DT')) define('DL_DT', ';');     // character to indicate a term (dt)
if (!defined('DL_DD')) define('DL_DD', ':');     // character to indicate a definition (dd)

// define the html used to generate the definition list
// - set to false or 0 to use simple list html <dl><dt>term</dt><dd>definition</dd> ... </dl>
// - set to true or 1 to use wrap the term element in a span permitting more complex styling
//   <dl><dt><span class='term'>term</span></dt><dd>definition</dd> ... </dl>
if (!defined('DL_FANCY')) define('DL_FANCY', true);

// -----------------------------------------------------------------

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_definitionlist extends DokuWiki_Syntax_Plugin {

    var $stack = array();

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Christopher Smith',
            'email'  => 'chris@jalakai.co.uk',
            'date'   => '2008-08-13',
            'name'   => 'Definition list plugin',
            'desc'   => 'Add HTML style definition list '.DL_DT.' term '.DL_DD.' definition',
            'url'    => 'http://www.dokuwiki.org/plugin:definitionlist',
        );
    }

    function getType() { return 'container'; }
    function getAllowedTypes() { return array('container','substition','protected','disabled','formatting'); }
    function getPType() { return 'block'; }          // block, so not surrounded by <p> tags
    function getSort() { return 10; }                // before preformatted (20)

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {

       $this->Lexer->addEntryPattern('\n {2,}'.DL_DT, $mode, 'plugin_definitionlist');
       $this->Lexer->addEntryPattern('\n\t{1,}'.DL_DT, $mode, 'plugin_definitionlist');

       $this->Lexer->addPattern('(?: '.DL_DD.' )', 'plugin_definitionlist');
       $this->Lexer->addPattern('\n {2,}(?:'.DL_DT.'|'.DL_DD.')', 'plugin_definitionlist');
       $this->Lexer->addPattern('\n\t{1,}(?:'.DL_DT.'|'.DL_DD.')', 'plugin_definitionlist');
    }

    function postConnect() {
        // we end the definition list when we encounter a blank line
        $this->Lexer->addExitPattern('\n[ \t]*\n','plugin_definitionlist');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        switch ( $state ) {
            case DOKU_LEXER_ENTER:      return array($state, 'dt');
            case DOKU_LEXER_MATCHED:    return array($state, (substr($match, -1) == DL_DT) ? 'dt' : 'dd');
            case DOKU_LEXER_EXIT:       return array($state, '');
            case DOKU_LEXER_UNMATCHED:
                    $handler->_addCall('cdata',array($match), $pos);
                    return false;
        }

        return false;
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if (empty($data)) return false;

        switch  ($mode) {
          case 'xhtml' : return $this->render_xhtml($renderer,$data);
          case 'odt' : return $this->render_odt($renderer,$data);
          default :
            //  handle unknown formats generically - by calling standard render methods
            list ($state, $param) = $data;
            switch ( $state ) {
               case DOKU_LEXER_ENTER:
                $renderer->p_open();
                break;
              case DOKU_LEXER_MATCHED:
                $renderer->p_close();
                $renderer->p_open();
                break;
              case DOKU_LEXER_UNMATCHED:                            // defensive, shouldn't occur
                $renderer->cdata($param);
                break;
              case DOKU_LEXER_EXIT:
                $renderer->p_close();
                break;
            }
            return true;
        }

        return false;
    }

    function render_xhtml(&$renderer, $data) {
        list ($state, $param) = $data;

        switch ( $state ) {
          case DOKU_LEXER_ENTER:
            $renderer->doc .= "\n<dl>\n";
            $renderer->doc .= $this->_open($param);
            break;
          case DOKU_LEXER_MATCHED:
            $renderer->doc .= $this->_close();
            $renderer->doc .= $this->_open($param);
            break;
          case DOKU_LEXER_UNMATCHED:                            // defensive, shouldn't occur
            $renderer->cdata($param);
            break;
          case DOKU_LEXER_EXIT:
            $renderer->doc .= $this->_close();
            $renderer->doc .= "</dl>\n";
            break;
        }
        return true;
    }

    /**
     * create output for ODT renderer
     *
     * @author:   Gabriel Birke <birke@d-scribe.de>
     */
    function render_odt(&$renderer, $data) {
        list ($state, $param) = $data;

        $param_styles = array('dd' => 'def_f5_list', 'dt' => 'def_f5_term');
        switch ( $state ) {
          case DOKU_LEXER_ENTER:
            $renderer->autostyles["def_f5_term"] = '
                  <style:style style:name="def_f5_term" style:display-name="def_term" style:family="paragraph">
                      <style:paragraph-properties fo:margin-top="0.18cm" fo:margin-bottom="0cm" fo:keep-together="always" style:page-number="auto" fo:keep-with-next="always"/>
                      <style:text-properties fo:font-weight="bold"/>
                  </style:style>';
            $renderer->autostyles["def_f5_list"] = '
                  <style:style style:name="def_f5_list" style:display-name="def_list" style:family="paragraph">
                      <style:paragraph-properties fo:margin-left="0.25cm" fo:margin-right="0cm" fo:text-indent="0cm" style:auto-text-indent="false"/>
                  </style:style>';
            $renderer->doc .= '</text:p>';
            $renderer->doc .= '<text:p  text:style-name="'.$param_styles[$param].'">';
            break;
          case DOKU_LEXER_MATCHED:
            $renderer->doc .= '</text:p>';
            $renderer->doc .= '<text:p  text:style-name="'.$param_styles[$param].'">';
            break;
          case DOKU_LEXER_UNMATCHED:                            // defensive, shouldn't occur
            $renderer->cdata($param);
            break;
          case DOKU_LEXER_EXIT:
            $renderer->doc .= '</text:p>';
            $renderer->p_open();
            break;
        }
        return true;
    }

    /**
     * open a definition list item, used by render_xhtml()
     * @param   $tag  (string)    'dt' or 'dd'
     * @return  (string)          html used to open the tag
     */
    function _open($tag) {
        array_push($this->stack, $tag);
        $wrap = (DL_FANCY && $tag == 'dt') ? "<span class='term'>" : "";
        return "<$tag>$wrap";
    }

    /**
     * close a definition list item, used by render_xhtml()
     * @return  (string)          html used to close the tag
     */
    function _close() {
        $tag = array_pop($this->stack);
        $wrap = (DL_FANCY && $tag == 'dt') ? "</span>" : "";
        return "$wrap</$tag>\n";
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
