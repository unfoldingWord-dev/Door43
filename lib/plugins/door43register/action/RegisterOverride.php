<?php
/**
 * Name: RegisterOverride.php
 * Description: A Dokuwiki action plugin to override the default register behavior.
 *
 * Author: Phil Hopper
 * Date:   2015-02-24
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_door43register_RegisterOverride extends DokuWiki_Action_Plugin {

    private $namespace;

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_register_action');
    }

    /**
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_register_action(Doku_Event &$event, /** @noinspection PhpUnusedParameterInspection */ $param) {

        if ($event->data !== 'register') return;

        //no other action handlers needed
        $event->stopPropagation();
        $event->preventDefault();


        $this->override_html_register();
    }

    protected function override_html_register(){
        global $lang;
        global $conf;
        global $INPUT;

        $base_attrs = array('size'=>50,'required'=>'required');
        $email_attrs = $base_attrs + array('type'=>'email','class'=>'edit');

        print $this->override_locale_xhtml('register');
        print '<div class="centeralign">'.NL;
        $form = new Doku_Form(array('id' => 'dw__register'));
        $form->startFieldset($lang['btn_register']);
        $form->addHidden('do', 'register');
        $form->addHidden('save', '1');
        $form->addElement(form_makeTextField('login', $INPUT->post->str('login'), $lang['user'], '', 'block', $base_attrs));
        if (!$conf['autopasswd']) {
            $form->addElement(form_makePasswordField('pass', $lang['pass'], '', 'block', $base_attrs));
            $form->addElement(form_makePasswordField('passchk', $lang['passchk'], '', 'block', $base_attrs));
        }
        $form->addElement(form_makeTextField('fullname', $INPUT->post->str('fullname'), $lang['fullname'], '', 'block', $base_attrs));
        $form->addElement(form_makeField('email','email', $INPUT->post->str('email'), $lang['email'], '', 'block', $email_attrs));
        $form->addElement(form_makeButton('submit', '', $lang['btn_register']));
        $form->endFieldset();
        html_form('register', $form);

        print '</div>'.NL;
    }

    protected function override_locale_xhtml($id){
        //fetch parsed locale
        $html = $this->override_cached_output($this->override_localeFN($id));
        return $html;
    }

    protected function override_localeFN($id, $ext='txt'){
        global $conf;
        $file = DOKU_CONF.'lang/'.$conf['lang'].'/'.$id.'.'.$ext;
        if(!@file_exists($file)){
            $file = DOKU_CONF.'lang/en/'.$id.'.'.$ext;
        }
        return $file;
    }

    protected function override_cached_output($file, $format='xhtml', $id='') {

        // we are not caching this page because it interferes with language switching
        $parsed = p_render($format, $this->override_cached_instructions($file,false,$id), $info);

        // if there is a user logged in, insert the translate button
        if (($GLOBALS['USERINFO'] != null) && (!empty($GLOBALS['USERINFO']['grps']))) {

            $ns = $this->getNamespace();
            if (!(empty($ns)) && ($ns != 'en'))
                $parsed .= PHP_EOL . $this->getButton();
        }

        return $parsed;
    }

    function override_cached_instructions($file, $cacheonly=false, $id='') {

        static $run = null;
        if(is_null($run)) $run = array();

        $cache = new cache_instructions($id, $file);

        if (@file_exists($file)) {
            // no cache - do some work
            $ins = p_get_instructions(io_readWikiPage($file, $id));
            if ($cache->storeCache($ins)) {
                $run[$file] = true; // we won't rebuild these instructions in the same run again
            } else {
                msg('Unable to save cache file. Hint: disk full; file permissions; safe_mode setting.',-1);
            }
            return $ins;
        }

        return null;
    }

    protected function getButton() {

        $buttonText = file_get_contents(dirname(dirname(__FILE__)) . '/templates/translate_button.html');

        // remove suppress comments
        $buttonText = preg_replace('/\<!--(\s)*suppress(.)*--\>(\n)/', '', $buttonText, 1);

        // translate
        $buttonText = $this->translateHtml($buttonText);

        /* @var $translation helper_plugin_door43translation */
        $translation = plugin_load('helper','door43translation');
        $langName = $translation->getLocalName($this->namespace);
        if (empty($langName)) $langName = $this->namespace;

        // insert the name of the current language and language code
        $buttonText = str_replace('{lang}', $langName, $buttonText);
        $buttonText = str_replace('{langCode}', $this->namespace, $buttonText);

        // put the text to translate into the edit area
        $fileText = file_get_contents($this->override_localeFN('register'));
        $buttonText = str_replace('</textarea>', $fileText . '</textarea>', $buttonText);

        return $buttonText;
    }

    protected function translateHtml($html) {
        return preg_replace_callback('/@(.+?)@/',
            function($matches) {
                $text = $this->getLang($matches[1]);
                return (empty($text)) ? $matches[0] : $text;
            },
            $html);
    }

    protected function getNamespace() {

        if (!empty($this->namespace)) return $this->namespace;

        global $INFO;

        $nss = explode(':', $INFO['namespace']);

        if (!empty($nss)) $this->namespace = $nss[0];

        return $this->namespace;
    }
}
