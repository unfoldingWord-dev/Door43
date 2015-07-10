<?php
/**
 * DokuWiki Plugin setedit (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Yvonne Lu <yvonnel@leapinglaptop.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_setedit extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler &$controller) {
        
        $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE', $this, 'handle_set_edit');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');
       
    }

    public function handle_set_edit (Doku_Event &$event, $param) {
        global $INPUT;
        global $lang;
        
        if(is_array($event->data)){
            list($act) = array_keys($event->data);
        } else {
            $act = $event->data;
        }
        //echo "in set_edit act=".$act."<br>";
        //$plugin_name=trim($INPUT->post->str('plugin'));
        //if ((strlen($plugin_name)===0) || (strcmp($plugin_name, 'setedit')!==0)){
        //    return true; //not called from setedit plugin
        //}
        
        if (!isset($_REQUEST['setedit'])) return;   // first time - nothing to do
        if (!checkSecurityToken()) return;
        
        if (isset($_POST['select_edit'])){
            $select_edit = $_POST['select_edit'];
            if ($select_edit==='dw'){
                //$this->editor='dw';
                setcookie("FCKG_USE", "_false_", time() + (86400 * 365), "/"); // 86400 = 1 day
                setcookie("SETEDIT", $_SERVER['REMOTE_USER']." dw", time() + (86400 * 365), "/"); // 86400 = 1 day
                msg(sprintf($this->getLang('set'), $this->getLang('dw_edit')), 1);
            }else {
                //$this->editor='ckg';
                
                setcookie("FCKG_USE", "other", time()+0, "/"); // expire this cookie
                setcookie("SETEDIT", $_SERVER['REMOTE_USER']." ckg", time() + (86400 * 365), "/"); // 86400 = 1 day
                msg(sprintf($this->getLang('set'), $this->getLang('ckgedit')), 1);
            }
        }
        
        
    }
    
    public function handle_action_act_preprocess(Doku_Event &$event, $param) {
        global $INPUT;
        global $lang;
        
        if(is_array($event->data)){
            list($act) = array_keys($event->data);
        } else {
            $act = $event->data;
        }
        //echo "act_preprocess:  act=".$act."<br>";
        
        if ($act==='edit'){
            
            if (isset($_COOKIE['SETEDIT'])){
                
                $editor_arr=explode(' ',$_COOKIE['SETEDIT']);
                echo "cookie user=".$editor_arr[0]."<br>";
                echo "cookie editor=".$editor_arr[1]."<br>";
                
                $user = trim($_SERVER['REMOTE_USER']);
                //echo "user=".$user."<br>";
                if (strcmp(trim($editor_arr[0]), $user)===0){
                    //set user default editor
                    if (trim($editor_arr[1])==='dw'){
                        echo "about to set FCKG_USE to false<br>";
                        setcookie("FCKG_USE", "_false_", time() + (86400 * 365), "/"); //1 year
                    }else {
                        echo "about to expire FCKG_USE<br>";
                        setcookie("FCKG_USE", "other", time() + 0, "/"); // expire now
                    }
                }
            }
            
        }
       
    }

   
}

// vim:ts=4:sw=4:et:
