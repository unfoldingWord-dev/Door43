<?php
/**
 * slackinvite Action Plugin:   Handle Upload and temporarily disabling cache of page.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author Yvonne Lu <yvonnel@leapinglaptop.com>
 * 
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once DOKU_PLUGIN . 'action.php';
require_once(DOKU_INC . 'inc/media.php');
require_once(DOKU_INC . 'inc/infoutils.php');

//define for debug
define ('RUN_STATUS', 'SERVER');



class action_plugin_slackinvite extends DokuWiki_Action_Plugin {

    var $fh=NULL;
    //var $tmpdir = NULL;
    
    function getInfo() {
        return array(
            'author' => 'Yvonne Lu',
            'email' => 'yvonnel@leapinglaptop.com',
            'date' => '2015-6-4',
            'name' => 'slackinvite plugin',
            'desc' => 'slackinvite plugin uploads a zip of usfm file to a given namespace then unzip it
            			Basic syntax: {{slackinvite}}',
            'url' => '',
        );
    }

    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_handle_slacksignup');
    }

    
    public function _handle_slacksignup(&$event, $param)
    {
        global $INPUT;


        if (! isset($event->data['slacksignup']))
            return false;

        $event->data = null; // clear the data because we want it to show the form after processing

        $err=false;

        $fn  = trim($INPUT->post->str('first_name'));
        $ln  = trim($INPUT->post->str('last_name')); 
        $this->showDebug('_handle_media_upload: fn= '.$fn." ln".$ln);
        if ((!preg_match("/^[a-zA-Z1-9]*$/",$fn)) ||
            (!preg_match("/^[a-zA-Z1-9]*$/",$ln)))    {
            msg ($this->getLang('name_err'),-1);
            $err=true;
        }
        $email = trim($INPUT->post->str('email')); 
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            msg($this->getLang('email_err'), -1);
            $err=true;
        }
       
        if (!$err){
            //<config>
            date_default_timezone_set('America/Phoenix');
            mb_internal_encoding("UTF-8");
            $slackHostName = $this->getConf('slack_host_name');
            $slackAutoJoinChannels =  $this->getConf('slack_auto_join_channels');
            $slackAuthToken = $this->getConf('slack_auth_token');
            //</config>
            //
            // <invite to slack>
                $slackInviteUrl='https://'.$slackHostName.'.slack.com/api/users.admin.invite?t='.time();

                $user['email']=$email;
                $user['fname']=$fn;
                $user['lname']=$ln;

                
                
                    $teststr= date('c').'- '."\"".$user['fname']."\" <".$user['email']."> - Inviting to ".$slackHostName." Slack\n";
                    $this->showDebug($teststr);
                    // <invite>
                            $fields = array(
                                    'email' => urlencode($user['email']),
                                    'channels' => urlencode($slackAutoJoinChannels),
                                    'first_name' => urlencode($user['fname']),
                                    'last_name' => urlencode($user['lname']),
                                    'token' => $slackAuthToken,
                                    'set_active' => urlencode('true'),
                                    '_attempts' => '1'
                            );

                    // url-ify the data for the POST
                            $fields_string='';
                            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
                            rtrim($fields_string, '&');

                            // open connection
                            $ch = curl_init();

                            // set the url, number of POST vars, POST data
                            curl_setopt($ch,CURLOPT_URL, $slackInviteUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch,CURLOPT_POST, count($fields));
                            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                            // exec
                            $replyRaw = curl_exec($ch);
                            $reply=json_decode($replyRaw,true);
                            if($reply['ok']==false) {
                                $txt = sprintf($this->getLang('invite_failed'), $user['fname'], $user['lname'], $user['email'], $reply['error']);
                                msg($txt, -1);
                             
                                
                                //$debugstr= date('c').' - '."\"".$user['fname']."\" <".$user['email']."> - ".'Error: '.$reply['error']."\n";
                            
                                $this->showDebug($txt);
                                $this->showDebug(curl_error($ch));
                            }
                            
                            else {
                                $txt = sprintf($this->getLang('invite_success'), $user['fname'], $user['lname'], $user['email']);
                                msg($txt, 1);
                                //$debugstr = date('c').' - '."\"".$user['fname']."\" <".$user['email']."> - ".'Invited successfully'."\n";
                                $this->showDebug($txt);
                                
                            }

                            // close connection
                            curl_close($ch);

                                    

                        // </invite>
                       
                
        // </invite to slack>
        }
        
        
    }
    
     private function showDebug($data) {
        if (strcmp(RUN_STATUS, 'DEBUG')==0){
            if ($this->fh==NULL) {
                $this->fh=fopen("slackinvite.txt", "a");
            }
            fwrite($this->fh, $data.PHP_EOL);
            fclose($this->fh);
            $this->fh=NULL;
        }
        
    }
    
    
}
