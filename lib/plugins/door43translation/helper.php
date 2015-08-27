<?php
/**
 * Name: helper.php
 * Description: The door43 language selection module
 *
 * Author: Phil Hopper
 * Date:   2015-08-24
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

/**
 * Class helper_plugin_door43translation
 */
class helper_plugin_door43translation extends helper_plugin_translation {

    var $LN = array(); // hold native names

    /**
     * Class constructor
     */
    function __construct() {

        parent::helper_plugin_translation();
    }

    /**
     * Creates an UI for linking to the available and configured translations
     *
     * Can be called from the template or via the ~~TRANS~~ syntax component.
     */
    public function showTranslations() {
        global $INFO;

        if(!$this->istranslatable($INFO['id'])) return '';
        $this->checkage();
        $out = '<div class="plugin_door43translation" style="width: 100%; box-sizing: border-box;">';

        //show title and about
        if(isset($this->opts['title'])) {
            $out .= '<span>' . $this->getLang('translations');
            if($this->getConf('about')) $out .= $this->showAbout();
            $out .= ':</span> ';
            if(isset($this->opts['twolines'])) $out .= '<br />';
        }

        $out .= $this->showAutoCompleteControls();

        // show about if not already shown
        if(!isset($this->opts['title']) && $this->getConf('about')) {
            $out .= '&nbsp';
            $out .= $this->showAbout();
        }
        $out .= '</div>';

        return $out;
    }

    /**
     * @return string
     */
    private function showAutoCompleteControls() {
        global $INFO;

        $out = '';
        $idPart = $this->getTransParts($INFO['id'])[1];

        // select needs its own styling
        if($INFO['exists']) {
            $class = 'wikilink1';
        }
        else {
            $class = 'wikilink2';
        }

        $script = file_get_contents(dirname(__FILE__) . DS . 'private' . DS . 'js' . DS . 'auto_complete_events.js');
        $out .= '<input type="hidden" id="namespace-auto-complete-action" value="' . $idPart . '">';
        $out .= $this->renderAutoCompleteTextBox('namespace-auto-complete', 'id', 'width: 100%', $class, $script);
        $out .= $this->renderRecentLanguages();
        return $out;
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $style
     * @param string $class
     * @param string $callbackScript
     * @return mixed|string
     */
    public function renderAutoCompleteTextBox($id, $name = '', $style = '', $class = '', $callbackScript = '') {

        $html = file_get_contents(dirname(__FILE__) . DS . 'private' . DS . 'html' . DS . 'auto_complete_language.html');
        $js = file_get_contents(dirname(__FILE__) . DS . 'private' . DS . 'js' . DS . 'sort_languages.js');

        // remove the initial doc comments
        $html = preg_replace('/^\<!--(\n|.)*?--\>(\n)?/U', '', $html, 1);

        // insert the sorting script
        $html = str_replace('/* insert sort_languages.js here - do not remove this comment */', $js, $html);

        // insert the callback script
        $html = str_replace('/* additional callback script - do not remove this comment */', $callbackScript, $html);

        // set id, name, style and class
        $html = str_replace('id=""', 'id="' . $id . '"', $html);
        $html = str_replace('#id', '#' . $id, $html);
        if (!empty($name))
            $html = str_replace('name=""', 'name="' . $name . '"', $html);
        if (!empty($style))
            $html = str_replace('style=""', 'style="' . $style . '"', $html);
        if (!empty($class))
            $html = str_replace('class=""', 'class="' . $class . '"', $html);

        return $html;
    }

    /**
     * @return mixed|string
     */
    private function renderRecentLanguages() {
        global $INFO;
        $currentLang = '';

        // get the possible language namespace
        $checkLang = explode(':', $INFO['id'], 2)[0];

        // is this an actual namespace?
        if (array_key_exists($checkLang, $this->LN))
            $currentLang = $checkLang . ':' . $this->LN[$checkLang];

        // load the html
        $html = file_get_contents(dirname(__FILE__) . DS . 'private' . DS . 'html' . DS . 'recent_languages.html');
        $html = str_replace('id="door43CurrentLanguage" value=""', 'id="door43CurrentLanguage" value="' . $currentLang . '"', $html);
        return $html;
    }
}
