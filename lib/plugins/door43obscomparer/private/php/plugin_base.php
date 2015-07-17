<?php
/**
 * Name: plugin_base.php
 * Description: A base class for the syntax plugins.
 *
 * Author: Phil Hopper
 * Date:   2014-12-10
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class door43obscomparer_plugin_base extends DokuWiki_Syntax_Plugin {

    protected $specialMatch;
    protected $entryMatch;
    protected $exitMatch;
    protected $newMode;
    protected $root;
    protected $templateFileName;

    function __construct($pluginName, $tagName, $templateFileName) {

        $this->root = dirname(dirname(__FILE__));
        $this->newMode = "plugin_door43obsaudioupload_{$pluginName}";
        $this->specialMatch = "\\[{$tagName}/\\]";
        $this->entryMatch = "\\[{$tagName}\\]";
        $this->exitMatch = "\\[/{$tagName}\\]";
        $this->templateFileName = $templateFileName;
    }

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
        return 'normal';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 901;
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

        $this->Lexer->addSpecialPattern($this->specialMatch, $mode, $this->newMode);
        $this->Lexer->addEntryPattern($this->entryMatch, $mode, $this->newMode);
        $this->Lexer->addExitPattern($this->exitMatch, $this->newMode);
    }

    /**
     * Handle matches of the door43obs syntax
     *
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler &$handler) {
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
    public function render($mode, Doku_Renderer &$renderer, $data) {

        if ($mode != 'xhtml') return false;

        $match = $data['match'];

        if (!$this->needToRender($match)) return false;

        $renderer->doc .= $this->getTextToRender($match);

        return true;
    }

    protected function needToRender($match) {

        // We don't need to do anything if the match was the "Entry" or "Exit" tag.
        if (preg_match('/(' . $this->entryMatch . '|' . str_replace('/', '\/', $this->exitMatch) . ')/', $match))
            return false;

        // We do want to handle the "special" tag and any "un-matched" text
        return true;
    }

    protected function getTextToRender(/** @noinspection PhpUnusedParameterInspection */
        $match) {

        // Load the template for the button
        $text = file_get_contents($this->root . '/html/' . $this->templateFileName);

        // remove the initial doc comments
        $text = preg_replace('/^\<!--(.|\n)*--\>(\n)/', '', $text, 1);

        $text = $this->translateHtml($text);

        return $text;
    }

    protected function translateHtml($html) {
        return preg_replace_callback('/@(.+)@/',
            function($matches) {
                $text = $this->getLang($matches[1]);
                return (empty($text)) ? $matches[0] : $text;
            },
            $html);
    }
}
