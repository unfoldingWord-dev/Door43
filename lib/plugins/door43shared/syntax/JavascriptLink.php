<?php
/**
 * Name: JavascriptLink.php
 * Description: A Dokuwiki action plugin to allow javascript in the href of an anchor tag.
 *
 * Author: Phil Hopper
 * Date:   2015-09-18
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


class syntax_plugin_door43shared_JavascriptLink extends DokuWiki_Syntax_Plugin {

    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'substition';
    }

    /**
     * @return string Paragraph type
     */
    public function getPType() {
        return 'block';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 40;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        // values for $mode
        // base
        // listblock
        // table
        // strong
        // emphasis
        // underline
        // monospace
        // subscript
        // superscript
        // deleted
        // footnote
        // quote

        $newMode = str_replace('syntax_', '', get_class($this));
        $this->Lexer->addSpecialPattern('\[\[javascript:.+\|.+\]\]', $mode, $newMode);
    }

    /**
     * Handle matches
     *
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        return array('match' => $match);
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string        $mode     Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array         $data     The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {

        if ($mode != 'xhtml') return false;

        // get the javascript and the text to render
        $match = $data['match'];
        preg_match('/\[\[(.+)\|(.+)\]\]/', $match, $parts);

        // this shouldn't happen, but just in case
        if (empty($parts)) return false;

        // render the match as a hyperlink
        $renderer->doc .= '<a href="' . $parts[1] . '">' . $parts[2] . '</a>';

        return true;
    }
}
