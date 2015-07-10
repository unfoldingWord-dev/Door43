<?php
/**
 * setedit plugin, allows users to set his editor preference
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author Yvonne Lu <yvonnel@leapinglaptop.com>
 *
 * 
 */

if(!defined('NL')) define('NL', "\n");
if(!defined('DOKU_INC')) define('DOKU_INC', dirname(__FILE__) . '/../../');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');
require_once(DOKU_INC . 'inc/media.php');
require_once(DOKU_INC . 'inc/auth.php');

require_once(DOKU_INC . 'inc/infoutils.php');

//define for debug
define ('RUN_STATUS', 'SERVER');

class syntax_plugin_setedit extends DokuWiki_Syntax_Plugin {

    var $fh=NULL; //debug file handle
    
    function getInfo() {
        return array(
            'author' => 'Yvonne Lu',
            'email' => 'yvonnel@leapinglaptop.com',
            'date' => '2015-6-24',
            'name' => 'setedit plugin',
            'desc' => 'setedit plugin dumps browser cookie {setedit}',
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
        
        $this->Lexer->addSpecialPattern('\{setedit\}', $mode, 'plugin_setedit');
    }

    function handle($match, $state, $pos, &$handler) {
        
    }

    function render($mode, &$renderer, $data) {
        global $ID;
  
        $this->showDebug('in render $mode='.$mode);
        $sel_str= '<input type="radio" name="select_edit" id = "dw" value="dw" checked="checked">'.
                            '<label for="dw">'.$this->getLang('dw_edit').'</label><br>'.
                        '<input type="radio" name="select_edit" id = "ckg" value="ckg">'.
                            '<label for="dw">'.$this->getLang('ckgedit').'</label><br>';
        if (isset($_COOKIE['SETEDIT'])){
            $editor_arr=explode(' ',$_COOKIE['SETEDIT']);
            $user = trim($_SERVER['REMOTE_USER']);
           if (strcmp(trim($editor_arr[0]), $user)===0){
                    //set user default editor
                    if (trim($editor_arr[1])!=='dw'){

                        $sel_str= '<input type="radio" name="select_edit" id = "dw" value="dw">'.
                            '<label for="dw">'.$this->getLang('dw_edit').'</label><br>'.
                        '<input type="radio" name="select_edit" id = "ckg" value="ckg" checked="checked">'.
                            '<label for="dw">'.$this->getLang('ckgedit').'</label><br>';
                    }
                    /*
                    //same user, check if user hasn't switched editor using DW editor button from ckgedit plugin
                    if ((isset($_COOKIE['FCKG_USE']))&& ($_COOKIE['FCKG_USE']==='_false_')){
                        //reset setedit cookie to dw editor
                        setcookie("SETEDIT", $_SERVER['REMOTE_USER']." dw", time() + (86400 * 365), "/"); // 86400 = 1 day
                    }else {
                        //set user default editor
                        if (trim($editor_arr[1])!=='dw'){

                            $sel_str= '<input type="radio" name="select_edit" id = "dw" value="dw">'.
                                '<label for="dw">'.$this->getLang('dw_edit').'</label><br>'.
                            '<input type="radio" name="select_edit" id = "ckg" value="ckg" checked="checked">'.
                                '<label for="dw">'.$this->getLang('ckgedit').'</label><br>';
                        }
                    }*/
                }
        }
       
        $this->str="<h1>".$this->getLang('title')."</h1><br>".
            "<p>".$this->getLang('desc')."</p>".
            '<form action="'.wl($ID).'" method="post">'.
            '<input type="hidden" name="plugin" value="'.$this->getPluginName().'" />'.
            formSecurityToken().   
            $sel_str.
            '<input type="submit" name="setedit"  value="'.$this->getLang('btn_change').'" />'.
            '</form>';
        
        $renderer->doc .= $this->str;
        $renderer->info['cache'] = false;
        return true;
        
  
    }

   
    
    private function showDebug($data) {
        if (strcmp(RUN_STATUS, 'DEBUG')==0){
            if ($this->fh==NULL) {
                $this->fh=fopen("setedit.txt", "a");
            }
            fwrite($this->fh, $data.PHP_EOL);
            fclose($this->fh);
            $this->fh = NULL;
        }
        

        
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
