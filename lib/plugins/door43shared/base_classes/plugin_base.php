<?php
/**
 * Name: plugin_base.php
 * Description: A base class for the syntax plugins.
 *
 * Author: Phil Hopper
 * Date:   2015-05-20
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


/**
 * This is a base class to use for Door43 syntax plugins.
 */
class Door43_Syntax_Plugin extends DokuWiki_Syntax_Plugin {

    protected $specialMatch;
    protected $entryMatch;
    protected $exitMatch;
    protected $newMode;
    protected $root;
    protected $templateFileName;

    /**
     * @param string $tagName Lower-case tag name
     * @param string $templateFileName Usually a HTML file
     */
    function __construct($tagName, $templateFileName) {

        $this->specialMatch = "\\[{$tagName}/\\]";  // self-closing tag: [tagName/]
        $this->entryMatch = "\\[{$tagName}\\]";     // opening tag:      [tagName]
        $this->exitMatch = "\\[/{$tagName}\\]";     // closing tag:      [/tagName]
        $this->templateFileName = $templateFileName;

        // set a unique mode name
        $this->newMode = str_replace('syntax_', '', get_class($this));

        // get the plugin root dir
        $ref = new ReflectionClass($this);
        $this->root = dirname($ref->getFileName());
        $pos = strpos($this->root, DS . 'syntax');
        if ($pos !== false) {
            $this->root = substr($this->root, 0, $pos);
        }
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

        $this->Lexer->addSpecialPattern(strtolower($this->specialMatch), $mode, $this->newMode);
        $this->Lexer->addEntryPattern(strtolower($this->entryMatch), $mode, $this->newMode);
        $this->Lexer->addExitPattern(strtolower($this->exitMatch), $this->newMode);
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

        $match = $data['match'];

        if (!$this->needToRender($match)) return false;

        $renderer->doc .= $this->getTextToRender($match);

        return true;
    }

    /**
     * @param string $match
     * @return bool
     */
    protected function needToRender($match) {

        // We don't need to do anything if the match was the "Entry" or "Exit" tag.
        if (preg_match('/(' . $this->entryMatch . '|' . str_replace('/', '\/', $this->exitMatch) . ')/i', $match))
            return false;

        // We do want to handle the "special" tag and any "un-matched" text
        return true;
    }

    /**
     * @param string $match
     * @return mixed|string
     */
    protected function getTextToRender(/** @noinspection PhpUnusedParameterInspection */ $match) {

        $html = $this->getHelper()->processTemplateFile($this->root . '/templates/' . $this->templateFileName);
        return $this->translateHtml($html);
    }

    /**
     * Strings that need translated are delimited by @ symbols. The text between the symbols is the key in lang.php.
     * @param $html
     * @return mixed
     */
    protected function translateHtml($html) {

        if (!$this->localised) $this->setupLocale();
        return $this->getHelper()->translateHtml($html, $this->lang);
    }

    /**
     * @return helper_plugin_door43shared
     */
    protected function getHelper() {

        /* @var $door43shared helper_plugin_door43shared */
        global $door43shared;

        // $door43shared is a global instance, and can be used by any of the door43 plugins
        if (empty($door43shared)) {
            $door43shared = plugin_load('helper', 'door43shared');
        }

        return $door43shared;
    }
}
