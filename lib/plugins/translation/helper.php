<?php
/**
 * Translation Plugin: Simple multilanguage plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_translation extends DokuWiki_Plugin {
    var $translations = array();
    var $translationNs = '';
    var $defaultLang = '';
    var $LN = array(); // hold native names
    var $opts = array(); // display options

    var $display_autoComplete = 2;

    /**
     * Initialize
     */
    function helper_plugin_translation() {
        global $conf;
        require_once(DOKU_INC . 'inc/pageutils.php');
        require_once(DOKU_INC . 'inc/utf8.php');

        // load wanted translation into array
        $this->translations = strtolower(str_replace(',', ' ', $this->getConf('translations')));
        $this->translations = array_unique(array_filter(explode(' ', $this->translations)));
        sort($this->translations);

        // load language names
        $this->LN = confToHash(dirname(__FILE__) . '/lang/langnames.txt');

        // display options
        $this->opts = $this->getConf('display');
        $this->opts = explode(',', $this->opts);
        $this->opts = array_map('trim', $this->opts);
        $this->opts = array_fill_keys($this->opts, true);

        // get default translation
        if(!$conf['lang_before_translation']) {
            $dfl = $conf['lang'];
        } else {
            $dfl = $conf['lang_before_translation'];
        }
        if(in_array($dfl, $this->translations)) {
            $this->defaultLang = $dfl;
        } else {
            $this->defaultLang = '';
            array_unshift($this->translations, '');
        }

        $this->translationNs = cleanID($this->getConf('translationns'));
        if($this->translationNs) $this->translationNs .= ':';
    }

    /**
     * Check if the given ID is a translation and return the language code.
     * @param $id
     * @return
     */
    function getLangPart($id) {
        list($lng) = $this->getTransParts($id);
        return $lng;
    }

    /**
     * Check if the given ID is a translation and return the language code and
     * the id part.
     * @param $id
     * @return array
     */
    function getTransParts($id) {
        $rx = '/^' . $this->translationNs . '(' . join('|', $this->translations) . '):(.*)/';
        if(preg_match($rx, $id, $match)) {
            return array($match[1], $match[2]);
        }
        return array('', $id);
    }

    /**
     * Returns the browser language if it matches with one of the configured
     * languages
     */
    function getBrowserLang() {
        $rx = '/(^|,|:|;|-)(' . join('|', $this->translations) . ')($|,|:|;|-)/i';
        if(preg_match($rx, $_SERVER['HTTP_ACCEPT_LANGUAGE'], $match)) {
            return strtolower($match[2]);
        }
        return false;
    }

    /**
     * Returns the ID and name to the wanted translation, empty
     * $lng is default lang
     * @param $lng
     * @param $idPart
     * @return array
     */
    function buildTransID($lng, $idPart) {
        //global $conf;
        if($lng) {
            $link = ':' . $this->translationNs . $lng . ':' . $idPart;
            $name = $lng;
        } else {
            $link = ':' . $this->translationNs . $idPart;
            $name = $this->realLC('');
        }
        return array($link, $name);
    }

    /**
     * Returns the real language code, even when an empty one is given
     * (eg. resolves th default language)
     * @param $lc
     * @return
     */
    function realLC($lc) {
        global $conf;
        if($lc) {
            return $lc;
        } elseif(!$conf['lang_before_translation']) {
            return $conf['lang'];
        } else {
            return $conf['lang_before_translation'];
        }
    }

    /**
     * Check if current ID should be translated and any GUI
     * should be shown
     * @param      $id
     * @param bool $checkAct
     * @return bool
     */
    function istranslatable($id, $checkAct = true) {
        global $ACT;

        if($checkAct && $ACT != 'show') return false;
        if($this->translationNs && strpos($id, $this->translationNs) !== 0) return false;
        $skipTrans = trim($this->getConf('skiptrans'));
        if($skipTrans && preg_match('/' . $skipTrans . '/ui', ':' . $id)) return false;
        $meta = p_get_metadata($id);
        if($meta['plugin']['translation']['notrans']) return false;

        return true;
    }

    /**
     * Return the (localized) about link
     */
    function showAbout() {
        global $ID;
        //global $conf;
        //global $INFO;

        $curlC = $this->getLangPart($ID);

        $about = $this->getConf('about');
        if($this->getConf('localabout')) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            list($lc, $idPart) = $this->getTransParts($about);
            /** @noinspection PhpUnusedLocalVariableInspection */
            list($about, $name) = $this->buildTransID($curlC, $idPart);
            $about = cleanID($about);
        }

        $out = '';
        $out .= '<sup>';
        $out .= html_wikilink($about, '?');
        $out .= '</sup>';

        return $out;
    }

    /**
     * Returns a list of (lc => link) for all existing translations of a page
     *
     * @param $id
     * @return array
     */
    function getAvailableTranslations($id) {
        $result = array();

        list($lc, $idPart) = $this->getTransParts($id);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $lang = $this->realLC($lc);

        foreach($this->translations as $t) {
            if($t == $lc) continue; //skip self
            list($link, $name) = $this->buildTransID($t, $idPart);
            if(page_exists($link)) {
                $result[$name] = $link;
            }
        }

        return $result;
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

        $out = '<div class="plugin_translation" style="width: 100%; box-sizing: border-box;">';

        //show title and about
        if(isset($this->opts['title'])) {
            $out .= '<span>' . $this->getLang('translations');
            if($this->getConf('about')) $out .= $this->showAbout();
            $out .= ':</span> ';
            if(isset($this->opts['twolines'])) $out .= '<br />';
        }

        if($this->getConf('dropdown') == $this->display_autoComplete)
            $out .= $this->showAutoCompleteControls();
        else
            $out .= $this->showStandardControls();

        // show about if not already shown
        if(!isset($this->opts['title']) && $this->getConf('about')) {
            $out .= '&nbsp';
            $out .= $this->showAbout();
        }

        $out .= '</div>';

        return $out;
    }

    /**
     * Displays the default dokuwiki language selection controls
     * @return string
     */
    private function showStandardControls() {

        global $conf;
        global $INFO;

        $out = '';

        list($lc, $idPart) = $this->getTransParts($INFO['id']);
        $lang = $this->realLC($lc);

        // open wrapper
        if($this->getConf('dropdown')) {
            // select needs its own styling
            if($INFO['exists']) {
                $class = 'wikilink1';
            } else {
                $class = 'wikilink2';
            }
            if(isset($this->opts['flag'])) {
                $flag = DOKU_BASE . 'lib/plugins/translation/flags/' . hsc($lang) . '.gif';
            }else{
                $flag = '';
            }

            if($conf['userewrite']) {
                $action = wl();
            } else {
                $action = script();
            }

            $out .= '<form action="' . $action . '" id="translation__dropdown">';
            if($flag) $out .= '<img src="' . $flag . '" alt="' . hsc($lang) . '" height="11" class="' . $class . '" /> ';

            $out .= '<select name="id" class="' . $class . '">';
        } else {
            $out .= '<ul>';
        }

        // insert items
        foreach($this->translations as $t) {
            $out .= $this->getTransItem($t, $idPart);
        }

        // close wrapper
        if($this->getConf('dropdown')) {

            // TODO: Replace this
            $out .= '</select>';
            $out .= '<input name="go" type="submit" value="&rarr;" />';
            $out .= '</form>';
        } else {
            $out .= '</ul>';
        }

        return $out;
    }

    private function showAutoCompleteControls() {

        global $INFO;

        $out = '';

        $idPart = $this->getTransParts($INFO['id'])[1];

        // select needs its own styling
        if($INFO['exists']) {
            $class = 'wikilink1';
        } else {
            $class = 'wikilink2';
        }

        $script = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'auto_complete_events.js');

        $out .= '<input type="hidden" id="namespace-auto-complete-action" value="' . $idPart . '">';

        $out .= $this->renderAutoCompleteTextBox('namespace-auto-complete', 'id', 'width: 100%', $class, $script);
        $out .= $this->renderRecentLanguages();

        return $out;
    }

    /**
     * Return the local name
     *
     * @param $lang
     * @return string
     */
    function getLocalName($lang) {
        if($this->LN[$lang]) {
            return $this->LN[$lang];
        }
        return $lang;
    }

    /**
     * Create the link or option for a single translation
     *
     * @param $lc string      The language code
     * @param $idpart string  The ID of the translated page
     * @returns string        The item
     */
    function getTransItem($lc, $idpart) {
        global $ID;
        global $conf;

        list($link, $lang) = $this->buildTransID($lc, $idpart);
        $link = cleanID($link);

        // class
        if(page_exists($link, '', false)) {
            $class = 'wikilink1';
        } else {
            $class = 'wikilink2';
        }

        // local language name
        $localname = $this->getLocalName($lang);

        // current?
        if($ID == $link) {
            $sel = ' selected="selected"';
            $class .= ' cur';
        } else {
            $sel = '';
        }

        // flag
        if(isset($this->opts['flag'])) {
            $flag = DOKU_BASE . 'lib/plugins/translation/flags/' . hsc($lang) . '.gif';
            $style = ' style="background-image: url(\'' . $flag . '\')"';
            $class .= ' flag';
        }
        else {
            $style = '';
            $flag = null;
        }

        // what to display as name
        if(isset($this->opts['name'])) {
            $display = hsc($localname);
            if(isset($this->opts['langcode'])) $display .= ' (' . hsc($lang) . ')';
        } elseif(isset($this->opts['langcode'])) {
            $display = hsc($lang);
        } else {
            $display = '&nbsp;';
        }

        // prepare output
        $out = '';
        if($this->getConf('dropdown')) {
            if($conf['useslash']) $link = str_replace(':', '/', $link);

            $out .= '<option class="' . $class . '" title="' . hsc($localname) . '" value="' . $link . '"' . $sel . $style . '>';
            $out .= $display;
            $out .= '</option>';
        } else {
            $out .= '<li><div class="li">';
            $out .= '<a href="' . wl($link) . '" class="' . $class . '" title="' . hsc($localname) . '">';
            if($flag) $out .= '<img src="' . $flag . '" alt="' . hsc($lang) . '" height="11" />';
            $out .= $display;
            $out .= '</a>';
            $out .= '</div></li>';
        }

        return $out;
    }

    /**
     * Checks if the current page is a translation of a page
     * in the default language. Displays a notice when it is
     * older than the original page. Tries to lin to a diff
     * with changes on the original since the translation
     */
    function checkage() {
        global $ID;
        global $INFO;
        if(!$this->getConf('checkage')) return;
        if(!$INFO['exists']) return;
        $lng = $this->getLangPart($ID);
        if($lng == $this->defaultLang) return;

        $rx = '/^' . $this->translationNs . '((' . join('|', $this->translations) . '):)?/';
        $idPart = preg_replace($rx, '', $ID);

        // compare modification times
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($orig, $name) = $this->buildTransID($this->defaultLang, $idPart);
        $origfn = wikiFN($orig);
        if($INFO['lastmod'] >= @filemtime($origfn)) return;

        // get revision from before translation
        $orev = 0;
        $revs = getRevisions($orig, 0, 100);
        foreach($revs as $rev) {
            if($rev < $INFO['lastmod']) {
                $orev = $rev;
                break;
            }
        }

        // see if the found revision still exists
        if($orev && !page_exists($orig, $orev)) $orev = 0;

        // build the message and display it
        $orig = cleanID($orig);
        $msg = sprintf($this->getLang('outdated'), wl($orig));
        if($orev) {
            $msg .= sprintf(
                ' ' . $this->getLang('diff'),
                wl($orig, array('do' => 'diff', 'rev' => $orev))
            );
        }

        echo '<div class="notify">' . $msg . '</div>';
    }

    public function renderAutoCompleteTextBox($id, $name = '', $style = '', $class = '', $callbackScript = '') {

        $html = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'auto_complete_language.html');

        // remove the initial doc comments
        $html = preg_replace('/^\<!--(\n|.)*?--\>(\n)?/U', '', $html, 1);

        // set id, name, style and class
        $html = str_replace('id=""', 'id="' . $id . '"', $html);
        $html = str_replace('#id', '#' . $id, $html);

        if (!empty($name))
            $html = str_replace('name=""', 'name="' . $name . '"', $html);

        if (!empty($style))
            $html = str_replace('style=""', 'style="' . $style . '"', $html);

        if (!empty($class))
            $html = str_replace('class=""', 'class="' . $class . '"', $html);

        $html = str_replace('/* additional callback script - do not remove this comment */', $callbackScript, $html);

        return $html;
    }

    private function renderRecentLanguages() {

        global $INFO;

        $currentLang = '';

        // get the possible language namespace
        $checkLang = explode(':', $INFO['id'], 2)[0];

        // is this an actual namespace?
        if (array_key_exists($checkLang, $this->LN))
            $currentLang = $checkLang . ':' . $this->LN[$checkLang];

        // load the html
        $html = file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'recent_languages.html');
        $html = str_replace('id="door43CurrentLanguage" value=""', 'id="door43CurrentLanguage" value="' . $currentLang . '"', $html);

        return $html;
    }
}
