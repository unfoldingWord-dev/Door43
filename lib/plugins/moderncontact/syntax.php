<?php

/* Modern Contact Plugin for Dokuwiki
 * 
 * Copyright (C) 2008 Bob Baddeley (bobbaddeley.com)
 * Copyright (C) 2010-2012 Marvin Thomas Rabe (marvinrabe.de)
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>. */

/**
 * Embed a contact form onto any page
 * @license GNU General Public License 3 <http://www.gnu.org/licenses/>
 * @author Bob Baddeley <bob@bobbaddeley.com>
 * @author Marvin Thomas Rabe <mrabe@marvinrabe.de>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/auth.php');
require_once(dirname(__file__).'/recaptchalib.php');

class syntax_plugin_moderncontact extends DokuWiki_Syntax_Plugin {

	public static $captcha = false;
	public static $lastFormId = 1;

	private $formId = 0;
	private $status = 1;
	private $statusMessage;
	private $errorFlags = array();

	/**
	 * General information about the plugin.
	 */
	public function getInfo(){
		return array(
			'author' => 'Marvin Thomas Rabe',
			'email'  => 'mrabe@marvinrabe.de',
			'date'	 => '2013-01-25',
			'name'	 => 'Modern Contact Plugin',
			'desc'	 => 'Creates a contact form to email the webmaster. Secured with recaptcha.',
			'url'	 => 'https://github.com/marvinrabe/dokuwiki-contact',
		);
	}

	/**
	 * What kind of syntax are we?
	 */
	public function getType(){
		return 'container';
	}

	/**
	 * What about paragraphs?
	 */
	public function getPType(){
		return 'block';
	}

	/**
 	 * Where to sort in?
 	 */
	public function getSort(){
		return 300;
	}

	/**
 	 * Connect pattern to lexer.
 	 */
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{contact>[^}]*\}\}',$mode,'plugin_moderncontact');
	}

	/**
	 * Handle the match.
	 */
	public function handle($match, $state, $pos, &$handler){
		if (isset($_REQUEST['comment']))
		    return false;

		$match = substr($match,10,-2); //strip markup from start and end

		$data = array();

		//handle params
		$params = explode('|',$match);
		foreach($params as $param){
			$splitparam = explode('=',$param);
			//multiple targets/profils possible for the email
			//add multiple to field in the dokuwiki page code
			// example : {{contact>to=profile1|to=profile2|subj=Feedback from Site}}
			if ($splitparam[0]=='to'){
				if (isset($data[$splitparam[0]])){
					$data[$splitparam[0]] .= ",".$splitparam[1]; //it is a "to" param but not the first
				}else{
					$data[$splitparam[0]] = $splitparam[1]; // it is the first "to" param
				}
			}else{
				$data[$splitparam[0]] = $splitparam[1]; // it is not a "to" param
			}
		}
		return $data;
	}

	/**
	 * Create output.
	 */
	public function render($mode, &$renderer, $data) {
		if($mode == 'xhtml'){
			// Define unique form id
			$this->formId = syntax_plugin_moderncontact::$lastFormId++;

			// Disable cache
			$renderer->info['cache'] = false;
			$renderer->doc .= $this->_contact($data);
			return true;
		}
		return false;
	}

	/**
	 * Verify and send email content.´
	 */
	protected function _send_contact($captcha=false){
		global $conf;
		global $auth;
		$lang = $this->getLang("error");

		require_once(DOKU_INC.'inc/mail.php');
		$name = $_POST['name'];
		$email = $_POST['email'];
		$subject = $_POST['subject'];
		$comment = $name."\r\n";
		$comment .= $email."\r\n\n";
		$comment .= $_POST['content'];
		if (isset($_REQUEST['to'])){
			//multiple targets/profils possible for the email
			$usersList = explode(',',$_POST['to']); 
			foreach($usersList as $userId){
				$user = $auth->getUserData($userId);
				if (isset($user)) {
					if (!empty($to)){
						$to .= ",".$user['mail'];
					}else{
						$to = $user['mail'];
					}
				}
			}
		} else {
			$to = $this->getConf('default');
		}

		// name entered?
		if(strlen($name) < 2)
			$this->_set_error('name', $lang["name"]);

		// email correctly entered?
		if(!$this->_check_email_address($email))
			$this->_set_error('email', $lang["email"]);

		// comment entered?
		if(strlen($_POST['content']) < 10)
			$this->_set_error('content', $lang["content"]);

		// checks recaptcha answer
		if($conf['plugin']['moderncontact']['captcha'] == 1 && $captcha == true) {
			$resp = recaptcha_check_answer ($conf['plugin']['moderncontact']['recaptchasecret'],
						$_SERVER["REMOTE_ADDR"],
						$_POST["recaptcha_challenge_field"],
						$_POST["recaptcha_response_field"]);
			if (!$resp->is_valid){
				$this->_set_error('captcha', $lang["captcha"]);
			}
		}

		// A bunch of tests to make sure it's legitimate mail and not spoofed
		// This should make it not very easy to do injection
		if (eregi("\r",$name) || eregi("\n",$name) || eregi("MIME-Version: ",$name) || eregi("Content-Type: ",$name)){
			$this->_set_error('name', $lang["valid_name"]);
		}
		if (eregi("\r",$email) || eregi("\n",$email) || eregi("MIME-Version: ",$email || eregi("Content-Type: ",$email))){
			$this->_set_error('email', $lang["valid_email"]);
		}
		if (eregi("\r",$subject) || eregi("\n",$subject) || eregi("MIME-Version: ",$subject) || eregi("Content-Type: ",$subject)){
			$this->_set_error('subject', $lang["valid_subject"]);
		}
		if (eregi("\r",$to) || eregi("\n",$to) || eregi("MIME-Version: ",$to) || eregi("Content-Type: ",$to)){
			$this->_set_error('to', $lang["valid_to"]);
		}
		if (eregi("MIME-Version: ",$comment) || eregi("Content-Type: ",$comment)){
			$this->_set_error('content', $lang["valid_content"]);
		}

		// Status has not changed.
		if($this->status != 0) {
			// send only if comment is not empty
			// this should never be the case anyway because the form has
			// validation to ensure a non-empty comment
			if (trim($comment, " \t") != ''){
				if (mail_send($to, $subject, $comment, $email, '', '', 'Reply-to: '.$email)){
					$this->statusMessage = $this->getLang("success");
				} else {
					$this->_set_error('unknown', $lang["unknown"]);
				}
				//we're using the included mail_send command because it's
				//already there and it's easy to use and it works
			}
		}

		return true;
	}

	/**
	 * Manage error messages.
	 */
	protected function _set_error($type, $message) {
		$this->status = 0;
		$this->statusMessage .= empty($this->statusMessage)?$message:'<br>'.$message;
		$this->errorFlags[$type] = true;
	}

	/**
	 * Validate email address. From: http://www.ilovejackdaniels.com/php/email-address-validation
	 */
	protected function _check_email_address($email) {
		// First, we check that there's one @ symbol, 
		// and that the lengths are right.
		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
			// Email invalid because wrong number of characters 
			// in one section or wrong number of @ symbols.
			return false;
		}
		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
				$local_array[$i])) {
					return false;
			}
		}
		// Check if domain is IP. If not, 
		// it should be valid domain name
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
				return false; // Not enough parts to domain
			}
			for ($i = 0; $i < sizeof($domain_array); $i++) {
				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$",
					$domain_array[$i])) {
						return false;
				}
			}
		}
		return true;
	}

	/**
	 * Does the contact form xhtml creation.
	 */
	protected function _contact($data){
		global $conf;
		global $USERINFO;

		// Is there none captche on the side?
		$captcha = ($conf['plugin']['moderncontact']['captcha'] == 1 && syntax_plugin_moderncontact::$captcha == false)?true:false;

		$ret = "<form action=\"".$_SERVER['REQUEST_URI']."#form-".$this->formId."\" method=\"POST\"><a name=\"form-".$this->formId."\"></a>";
		$ret .= "<table class=\"inline\">";

		// Send message and give feedback
		if (isset($_POST['submit-form-'.$this->formId]))
			if($this->_send_contact($captcha))
				$ret .= $this->_show_message();

		// Build table
		$ret .= $this->_table_row($this->getLang("name"), 'name', 'text', $USERINFO['name']);
		$ret .= $this->_table_row($this->getLang("email"), 'email', 'text', $USERINFO['mail']);
		if (!isset($data['subj']))
			$ret .= $this->_table_row($this->getLang("subject"), 'subject', 'text');
		$ret .= $this->_table_row($this->getLang("content"), 'content', 'textarea');

		// Captcha
		if($captcha) {
			if($this->errorFlags["captcha"]) {
				$ret .= '<style>#recaptcha_response_field { border: 1px solid #e18484 !important; }</style>';
			}
			$ret .= "<tr><td colspan=\"2\">"
			. "<script type=\"text/javascript\">var RecaptchaOptions = { lang : '".$conf['lang']."', "
			. "theme : '".$conf['plugin']['moderncontact']['recaptchalayout']."' };</script>"
			. recaptcha_get_html($conf['plugin']['moderncontact']['recaptchakey'])."</td></tr>";
			syntax_plugin_moderncontact::$captcha = true;
		}

		$ret .= "</table><p>";
		if (isset($data['subj']))
			$ret .= "<input type=\"hidden\" name=\"subject\" value=\"".$data['subj']."\" />";
		if (isset($data['to']))	
			$ret .= "<input type=\"hidden\" name=\"to\" value=\"".$data['to']."\" />";
		$ret .= "<input type=\"hidden\" name=\"do\" value=\"show\" />";
		$ret .= "<input type=\"submit\" name=\"submit-form-".$this->formId."\" value=\"".$this->getLang("contact")."\" />";
		$ret .= "</p></form>";

		return $ret;
	}

	/**
	 * Show up error messages.
	 */
	protected function _show_message() {
		return '<tr><td colspan="2">'
		. '<p class="'.(($this->status == 0)?'contact_error':'contact_success').'">'.$this->statusMessage.'</p>'
		. '</td></tr>';
	}

	/**
	 * Renders a table row.
	 */
	protected function _table_row($label, $name, $type, $default='') {
		$value = (isset($_POST['submit-form-'.$this->formId]) && $this->status == 0)?$_POST[$name]:$default;
		$class = ($this->errorFlags[$name])?'class="error_field"':'';
		$row = '<tr><td>'.$label.'</td><td>';
		if($type == 'textarea')
			$row .= '<textarea name="'.$name.'" wrap="on" cols="40" rows="6" '.$class.'>'.$value.'</textarea>';
		else
			$row .= '<input type="'.$type.'" value="'.$value.'" name="'.$name.'" '.$class.'>';
		$row .= '</td></tr>';
		return $row;
	}

}
