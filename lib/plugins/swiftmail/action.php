<?php
/**
 * SwiftMailer plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_swiftmail extends DokuWiki_Action_Plugin {

    /**
     * register the eventhandlers
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function register(&$controller){
        $controller->register_hook('MAIL_MESSAGE_SEND',
                                   'BEFORE',
                                   $this,
                                   'handle_message_send',
                                   array());
    }

    /**
     * Handle the message send event and use SwiftMailer to mail
     */
    function handle_message_send(&$event, $param){
        require_once dirname(__FILE__).'/Swift.php';
        require_once dirname(__FILE__).'/Swift/Connection/SMTP.php';
        $ok = false;
        if($this->getConf('debug')){
            $log =& Swift_LogContainer::getLog();
            $log->setLogLevel(Swift_Log::LOG_EVERYTHING);
        }

        try {
            // initialize the connection
            $smtp =& new Swift_Connection_SMTP(
                        $this->getConf('smtp_host'),
                        $this->getConf('smtp_port'),
                        $this->getConf('smtp_ssl')
                     );

            // use Pop-before-SMTP
            if($this->getConf('pop3_host')) {
                require_once dirname(__FILE__).'/Swift/Authenticator/@PopB4Smtp.php';
                $smtp->attachAuthenticator(new Swift_Authenticator_PopB4Smtp($this->getConf('pop3_host')));
            }

            // use SMTP auth?
            if($this->getConf('auth_user')) $smtp->setUsername($this->getConf('auth_user'));
            if($this->getConf('auth_pass')) $smtp->setPassword($this->getConf('auth_pass'));

            // start Swift
            $swift =& new Swift($smtp,$this->getConf('localdomain'));

            // prepare message (Swift autodetects UTF-8)
            $message =& new Swift_Message($event->data['subject'], $event->data['body']);

            // did we get an Adora Belle Mailer object?
            if(isset($event->data['mail']) && is_a($event->data['mail'],'Mailer')){
                // we'd need to call cleanHeaders() here, but it's protected in Adora Belle.
                // instead we call the dump() method which will call cleanHeaders for us
                if(is_callable(array($event->data['mail'],'cleanHeaders()'))){
                    $event->data['mail']->cleanHeaders();
                }else{
                    $event->data['mail']->dump();
                }
            }

            // handle the recipients (duplicates some code from mail_encode_address)
            $reci =& new Swift_RecipientList();
            $from = null;
            $num  = 0;
            foreach(array('to','cc','bcc','from') as $hdr){
                $parts = explode(',',$event->data[$hdr]);
                foreach ($parts as $part){
                    $part = trim($part);

                    // parse address
                    if(preg_match('#(.*?)<(.*?)>#',$part,$matches)){
                        $text = trim($matches[1]);
                        $addr = $matches[2];
                    }else{
                        $addr = $part;
                    }

                    // skip empty ones
                    if(empty($addr)) continue;

                    // add
                    if($hdr == 'from'){
                        $from =& new Swift_Address($addr,$text);
                    }else{
                        if($hdr == 'to' || $hdr == 'cc') $num++;
                        if($hdr == 'bcc' && $num == 0){
                            // no to and cc - add bcc as to and send as batch later
                            $reci->add($addr,$text,'to');
                        }else{
                            $reci->add($addr,$text,$hdr);
                        }
                    }
                }
            }

            // now finally send the mail
            if($num){
                $ok = $swift->send($message, $reci, $from);
            }else{
                $ok = $swift->batchSend($message, $reci, $from);
            }
        } catch (Swift_ConnectionException $e) {
            msg('There was a problem communicating with SMTP: '.$e->getMessage(),-1);
        } catch (Swift_Message_MimeException $e) {
            msg('There was an unexpected problem building the email: '.$e->getMessage(),-1);
        } catch(Exception $e){
            msg('There was an unexpected problem with sending the email: '.$e->getMessage(),-1);
        }

        if(!$ok && $this->getConf('debug')){
            $dbglog = $log->dump(true);
            $dbglog = preg_replace('/(AUTH \w+ ).*$/m','\\1 ***',$dbglog); //filter out passwords
            $dbglog = preg_replace('/(PASS ).*$/m','\\1 ***',$dbglog); //filter out passwords
            msg('SwiftMailer log:<br /><pre>'.hsc($dbglog).'</pre>',-1);
        }

        $event->preventDefault();
        $event->stopPropagation();
        $event->result = $ok;
        $event->data['success'] = $ok;
    }
}

