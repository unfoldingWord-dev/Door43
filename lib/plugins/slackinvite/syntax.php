<?php
/**
 * slackinvite plugin, a sign up form for users who want to join the team43's
 * channel on slack 
 * 
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author Yvonne Lu <yvonnel@leapinglaptop.com>
 *
 */

if(!defined('NL')) define('NL', "\n");
if(!defined('DOKU_INC')) define('DOKU_INC', dirname(__FILE__) . '/../../');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');
//require_once(DOKU_INC . 'inc/media.php');
//require_once(DOKU_INC . 'inc/auth.php');

//require_once(DOKU_INC . 'inc/infoutils.php');

//define for debug
define ('RUN_STATUS', 'SERVER');

class syntax_plugin_slackinvite extends DokuWiki_Syntax_Plugin {

    var $fh=NULL; //debug file handle
    
    function getInfo() {
        return array(
            'author' => 'Yvonne Lu',
            'email' => 'yvonnel@leapinglaptop.com',
            'date' => '2015-6-3',
            'name' => 'slackinvite plugin',
            'desc' => 'slackinvite plugin a sign up form for users who want to '
                        .'join the team43 channel on slack ' 
            		.'Basic syntax: {{slackinvite}}',
            'url' => '',
        );
    }

    function getType() {
        return 'substition';
    }

    function getSort() {
        return 32;
    }

    function connectTo($mode) {
        //$this->Lexer->addSpecialPattern('\{\{slackinvite>.+?\}\}', $mode, 'plugin_slackinvite');
        $this->Lexer->addSpecialPattern('\{slackinvite\}', $mode, 'plugin_slackinvite');
    }

    function handle($match, $state, $pos, &$handler) {
        
        global $ID;

        $options['overwrite'] = TRUE;
        $options['renameable'] = TRUE;

        
        $ns = getNS($ID);
       

        return array('uploadns' => hsc($ns), 'para' => $options);
    }

    function render($mode, &$renderer, $data) {
        
        $this->showDebug('in render $mode='.$mode);
        $renderer->doc .= $this->slackinvite_signinform();
        $renderer->info['cache'] = false;
        return true;
    }

    /**
     * form for slack invite
     *
     *
     */
    function slackinvite_signinform() {
        global $ID;
        $html = '';
        
        $params = array();
        $params['id'] = 'slackinvite_plugin_id';
        $params['action'] = wl($ID);
        $params['method'] = 'post';
        $params['enctype'] = 'multipart/form-data';
        $params['class'] = 'slackinvite_plugin';

        // Modification of the default dw HTML upload form
        $form = new Doku_Form($params);
        $form->startFieldset($this->getLang('signup'));
        $form->addHidden('source', hsc("slackinvite")); //add source of call, used in action to ignore anything not from this form

        $form->addElement(form_makeTextField('first_name', '', $this->getLang('first_name'), 'first__name'));
        $form->addElement(form_makeTextField('last_name', '', $this->getLang('last_name'), 'last__name'));
        $form->addElement(form_makeTextField('email', '', $this->getLang('email'), 'email'));
        $form->addElement(form_makeButton('submit', 'slacksignup', $this->getLang('btn_signup')));
        $form->endFieldset();

        $html .= '<div class="dokuwiki"><p>' . NL;
        //$html .= '<h3>TEAM43 Slack Sign Up</h3>';
        $html .= $form->getForm();
        $html .= '</p></div>' . NL;
        return $html;
    }
    
    private function showDebug($data) {
        if (strcmp(RUN_STATUS, 'DEBUG')==0){
            if ($this->fh==NULL) {
                $this->fh=fopen("slackinvite.txt", "a");
            }
            fwrite($this->fh, $data.PHP_EOL);
            fclose($this->fh);
            $this->fh = NULL;
        }
        

        
    }
}
