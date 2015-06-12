<?php
//if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_INC')) define('DOKU_INC',fullpath(dirname(__FILE__).'/../').'/');
if(!defined('DOKU_PLUGIN'))  define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
 
/**
 *  nsmsg admin plugin
 * 
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 *  
 * 
 * 2-24-15 Updated the way icons are included in message type display
 * in _printmsgbar
 * 
 */
class admin_plugin_nsmsg extends DokuWiki_Admin_Plugin {
    
    var $output = 'world';
    /**
     * Constructor
     */
    function admin_plugin_message() {
        $this->setupLocale();
    }

    /**
     * return some info
     */
    function getInfo() {
        //no page description yet
        //'url'    => 'https://github.com/Door43/nsmsg',
        return array(
            'author' => 'Yvonne Lu',
            'email'  => 'yvonne@leapinglaptop.com',
            'date'   => @file_get_contents(DOKU_PLUGIN.'nsmsg/VERSION'),
            'name'   => 'namespace message Plugin',
            'desc'   => 'nsmsg namespace and message administration',
            'url'    => 'https://github.com/Door43/nsmsg',
        );
    }
 
    /**
     * return prompt for admin menu
     */
    function getMenuText($language) {
      if (!$this->disabled)
        return parent::getMenuText($language);
      return '';
    }
                                                    
    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 5000;
    }
 
    /**
     * handle user request
     */
    function handle() {
        
        global $conf;
        
        if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do
        if (!checkSecurityToken()) return;
        if (!is_array($_REQUEST['cmd'])) return;
       
        $msg_num=0;
        if (isset($_POST['total'])){
            $msg_num=$_POST['total']; //total number of previous messages
        }
        $filename = $conf['cachedir']."/nsmsg.xml";
        
        //overwrite existing message file
        if (is_writable( $filename ) || (is_writable(dirname( $filename ).'/.'))) {
            if ($handle = fopen($filename, 'w')) {
                fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
                fwrite($handle,'<messages>'.PHP_EOL);
                
                for ($i = 0; $i <= $msg_num; $i++) {
                    
                    if (isset($_POST['del'.$i])){
                        //delete is set so skip this message
                        
                        continue;
                        
                    }
                    if (isset($_POST['msg'.$i])){
                        if (strlen(trim($_POST['msg'.$i]))==0) {
                            continue;
                        }
                        fwrite($handle, '<message>'.PHP_EOL);
                        fwrite($handle,'<body>'.$_POST['msg'.$i].'</body>'.PHP_EOL);
                        
                    }                    
                    if (isset($_POST['link'.$i])) {
                        fwrite($handle,'<link>'.$_POST['link'.$i].'</link>'.PHP_EOL);
                        
                    } 
                    
                    if (isset($_POST['ns'.$i])) {
                        fwrite($handle,'<namespace>'.$_POST['ns'.$i].'</namespace>'.PHP_EOL);
                        
                        
                    }   
                    if (isset($_POST['msg_type'.$i])) {
                        fwrite($handle,'<type>'.$_POST['msg_type'.$i].'</type>'.PHP_EOL);
                        fwrite($handle, '</message>'.PHP_EOL);
                        
                    }   
                } 
                fwrite($handle,'</messages>'.PHP_EOL);
               
                fclose($handle);
            }else {
                msg($this->getPluginName().' '.$this->getLang('error_f')." ($filename).",-1);                
            }
        }else {
            msg($this->getPluginName().' '.$this->getLang('error_d')." (".$conf['cachedir'].").",-1);
                
        }
        
        
    }
 
    /**
     * output appropriate html
     */
    function html() {
        //print current message and namespace
        global $lang;
        global $conf;
      
        print "<h1>".$this->getLang('mess_titre')."</h1>";
        print $this->getLang('mess_texte');
        print "<br /><br />";
        
        ptln('<form action="'.wl($ID).'" method="post">');
        // output hidden values to ensure dokuwiki will return back to this plugin
        ptln('  <input type="hidden" name="do"   value="admin" />');
        ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
        formSecurityToken();

        //check for previous messages
        $filename = $conf['cachedir']."/nsmsg.xml";
        
        $idx=0;
        $total_msg =0;
        if (file_exists($filename)) {
            try { 
                $xml=simplexml_load_file($filename);
                //print existing messages
                
                echo "<strong>".$this->getLang('cur_title')."</strong><br>";
                $total_msg = $xml->count();
                foreach ($xml as $message) {
                    print $this->getLang('mess_label')."<textarea name='msg$idx' style=\"width:100%; background-color: lightblue\">";
                    print $message->body."</textarea><br>";
                    print $this->getLang('link_label')."<textarea name='link$idx' style=\"width:100%; background-color: lightblue\">";
                    print $message->link."</textarea><br>";
                    print $this->getLang('ns_label')."<textarea name='ns$idx' style=\"width:100%; background-color: lightblue\">";
                    print $message->namespace."</textarea><br>";
                    print $this->getLang('type_label')."<br>";
                    $this->_printmsgbar($message->type, $idx);
                    echo '<br><br>';
                    
                    echo ("<input type='checkbox' name='del$idx'>".$this->getLang('btn_del')."<br><br>");
                    
                    $idx++;
                }
                
               
            } catch (Exception $e) { 
                echo $this->getLang('error_c'); 
            } 
            
        }
        ptln('<input type="hidden" name="total"   value="'.$total_msg.'" />'); //get number of existing messages
        echo "<hr><strong>".$this->getLang('add_title')."</strong><br>";
        
        //msg, link and namespace input field
        
        print $this->getLang('mess_label')."<textarea name='msg$idx' style=\"width:100%; background-color: lightgray\">";
        print "</textarea>";
        print "<br>";
        
        print $this->getLang('link_label')."<textarea name='link$idx' style=\"width:100%; background-color: lightgray\">";
        print "</textarea>";
        
        print $this->getLang('ns_label')."<textarea name='ns$idx' style=\"width:100%; background-color: lightgray\">";
        print "</textarea>";
        print "<br><br>";
        $this->_printmsgbar('-1', $idx);
        
       
        print "<br><br>";

        
        ptln('  <input type="submit" name="cmd[change]"  value="'.$this->getLang('btn_change').'" />');
        
        ptln('</form>');
        
     

    }
    
    //selection -1=error, 0=info, 1=confirmation, 2=notification
    function _printmsgbar($sel, $idx){
        
        //$imgs = array("error.png", "info.png", "success.png", "notify.png");
        //$colors = array("#ffcccc", "#ccccff", "#ccffcc", "#ffffcc" );
        $msgs = array ("error_msg", "info_msg", "conf_msg", "note_msg");
        $values=array ("-1", "0", "1", "2");
        $class= array ("error", "info", "success", "notify");
        
        for ($i=0; $i<4; $i++){
            $checked = (strcmp($sel, $values[$i])==0) ? 'checked' : '';
          
            echo "<div style='display:inline;' class='$class[$i]'>
                <input type='radio' name='msg_type$idx' value=$values[$i] $checked>&nbsp;".
                $this->getLang($msgs[$i])."&nbsp;
                </div>";
            /*
            $imgpath = join(DIRECTORY_SEPARATOR, array(DOKU_BASE, 'lib', 'images',$imgs[$i]));
            echo "<span style='background-color:$colors[$i];padding:0.2em;'>
                <input type='radio' name='msg_type$idx' value=$values[$i] $checked>
                <img src='$imgpath'>&nbsp;".
                $this->getLang($msgs[$i])."&nbsp;
                </span>";*/
            
        }
        
      
        
    }

}                                                                                                                                                                  

