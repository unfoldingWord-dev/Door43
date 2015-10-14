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

    /**
     * @var door43Cache
     */
    private $cache;
    private $htmlLangCode;
    private $htmlLangDir;

    /**
     * Class constructor
     */
    function __construct() {
        parent::helper_plugin_translation();

        $this->load_from_cache();
    }

    private function load_from_cache() {

        /* @var $cache door43Cache */
        $cache = $this->getCache();
        $cacheFile = 'helperLN.json';
        $ln = $cache->getObject($cacheFile, true);

        if (empty($ln)) {
            $this->get_language_data();
            $ln = $cache->getObject($cacheFile, true);
        }

        $this->LN = $ln;

        $translations = $cache->getObject('translations.json', true);
        if (empty($translations)) {
            $this->get_language_data();
            $translations = $cache->getObject('translations.json', true);
        }

        $this->translations = $translations;
    }

    private function get_language_data() {

        /* @var $cache door43Cache */
        $cache = $this->getCache();
        $cacheFile = 'langnames.json';
        $langs = $cache->getObject($cacheFile, true);

        // download from api.unfoldingWord.org if needed
        if (empty($langs)) {

            $http = new DokuHTTPClient();
            $raw = $http->get('https://td.unfoldingword.org/exports/langnames.json');
            $langs = json_decode($raw, true);
            $cache->saveString($cacheFile, $raw);
        }

        // if still empty, use the backup copy
        if (empty($langs)) {
            $langs = json_decode(file_get_contents(dirname(__FILE__) . '/lang/langnames.json'), true);
        }

        // $this->LN
        // $this->translations
        $ln = array();
        $translations = '';
        $langDir = array();

        foreach($langs as $lang) {
            $ln[$lang['lc']] = $lang['ln'];
            $translations .= ' ' . $lang['lc'];
            $langDir[$lang['lc']] = $lang['ld'];
        }

        $cache->saveObject('helperLN.json', $ln);
        $cache->saveObject('languageDirection.json', $langDir);

        $sorted = array_unique(array_filter(explode(' ', $translations)));
        sort($sorted);
        $cache->saveObject('translations.json', $sorted);
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

        // remove the initial doc comments
        $html = preg_replace('/^\<!--(\n|.)*?--\>(\n)?/U', '', $html, 1);

        // set id, name, style and class
        $html = str_replace('id=""', 'id="' . $id . '"', $html);
        if (!empty($name))
            $html = str_replace('name=""', 'name="' . $name . '"', $html);
        if (!empty($style))
            $html = str_replace('style=""', 'style="' . $style . '"', $html);
        if (!empty($class))
            $html = str_replace('class=""', 'class="' . $class . '"', $html);

        // insert the callback script
        if (!empty($callbackScript)) {
            $html .= "<script type=\"text/javascript\">\n";
            $html .= "jQuery().ready(function() {\n";
            $html .= $callbackScript;
            $html .= "});\n";
            $html .= "</script>\n";
        }

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

    /**
     * Check if the given ID is a translation and return the language code and
     * the id part.
     * @param $id
     * @return array
     */
    function getTransParts($id) {

        // Reworking because of this error when using over 7000 languages:
        //   'Warning: preg_match(): Compilation failed: regular expression is too large at offset 29940'
        $parts = explode(':', $id, 2);
        if ((count($parts) > 0) && in_array($parts[0], $this->translations)) {
            if (count($parts) == 2) {
                return $parts;
            }
            else {
                return array($parts[0], '');
            }
        }
        return array('', $id);
    }

    private function getCache() {

        if (empty($this->cache)) {

            /* @var $door43shared helper_plugin_door43shared */
            global $door43shared;

            if (empty($door43shared)) {
                $door43shared = plugin_load('helper', 'door43shared');
            }

            $this->cache = $door43shared->getCache();
        }

        return $this->cache;
    }

    public function getHtmlLang() {
        global $INFO;

        if (empty($this->htmlLangCode)) {
            $lang_code = $this->getTransParts($INFO['id'])[0];
            $this->htmlLangCode = (empty($lang_code)) ? 'en': $lang_code;
        }

        return $this->htmlLangCode;
    }

    public function getHtmlLangDir() {

        if (empty($this->htmlLangDir)) {

            /* @var $cache door43Cache */
            $cache = $this->getCache();
            $cacheFile = 'languageDirection.json';
            $lang_dir = $cache->getObject($cacheFile, true);
            if (empty($this->htmlLangDir)) {
                $this->get_language_data();
                $lang_dir = $cache->getObject($cacheFile, true);
            }

            $lang_code = $this->getHtmlLang();
            $this->htmlLangDir = (empty($lang_dir[$lang_code])) ? 'ltr': $lang_dir[$lang_code];
        }

        return $this->htmlLangDir;
    }
}
