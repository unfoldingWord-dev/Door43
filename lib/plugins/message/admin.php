<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
 
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_message extends DokuWiki_Admin_Plugin {

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
        return array(
            'author' => 'Etienne M.',
            'email'  => 'emauvaisfr@yahoo.fr',
            'date'   => @file_get_contents(DOKU_PLUGIN.'message/VERSION'),
            'name'   => 'message Plugin',
            'desc'   => 'Gestion des messages / Messages administration',
            'url'    => 'http://www.dokuwiki.org/plugin:message',
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
    }
 
    /**
     * output appropriate html
     */
    function html() {
      global $lang;
      global $conf;
                                    
      print "<h1>".$this->getLang('mess_titre')."</h1>";
      print $this->getLang('mess_texte');
      print "<br /><br />";

      if (isset($_POST['sauver']) && $_POST['sauver']==1) {
        $ok=true;
        $ok = $ok & $this->ecritFichier($conf['cachedir'].'/message_error.txt', $_POST['err']);
        $ok = $ok & $this->ecritFichier($conf['cachedir'].'/message_info.txt', $_POST['info']);
        $ok = $ok & $this->ecritFichier($conf['cachedir'].'/message_valid.txt', $_POST['valid']);
        $ok = $ok & $this->ecritFichier($conf['cachedir'].'/message_remind.txt', $_POST['rappel']);

        if ($ok) {
          print "<form name=\"message_form\" method=\"POST\"><input type=\"hidden\" name=\"sauver\" value=\"0\" /></form>";
          print "<script>document.forms['message_form'].submit();</script>";
        }
        else {
          msg("<b>".$this->getLang('mess_erreurs')."</b>",-1);
          print "<br />";
        }
      }

      print "<form method=\"POST\">";
      print "<input type=\"hidden\" name=\"sauver\" value=\"1\" />";

      msg($this->getLang('mess_err'),-1);
      $file=$conf['cachedir'].'/message_error.txt';
      print "<textarea name=\"err\" style=\"width:100%\">";
      print @file_get_contents($file);
      print "</textarea>";
      print "<br /><br />";

      msg($this->getLang('mess_info'),0);
      $file=$conf['cachedir'].'/message_info.txt';
      print "<textarea name=\"info\" style=\"width:100%\">";
      print @file_get_contents($file);
      print "</textarea>";
      print "<br /><br />";

      msg($this->getLang('mess_ok'),1);
      $file=$conf['cachedir'].'/message_valid.txt';
      print "<textarea name=\"valid\" style=\"width:100%\">";
      print @file_get_contents($file);
      print "</textarea>";
      print "<br /><br />";

      msg($this->getLang('mess_rappel'),2);
      $file=$conf['cachedir'].'/message_remind.txt';
      print "<textarea name=\"rappel\" style=\"width:100%\">";
      print @file_get_contents($file);
      print "</textarea>";
      print "<br /><br />";

      print "<input type=\"submit\" value=\"".$this->getLang('mess_sauver')."\" title=\"".$this->getLang('mess_sauver')." [S]\" accesskey=\"s\" />";
      print "</form>";

    }

    function ecritFichier($fic,$chaine) {
      //if (is_writable($fic)) {
      if (is_writable( $fic ) || (is_writable(dirname( $fic ).'/.') && !file_exists( $fic ))) {
        if (!$handle = fopen($fic, 'w')) {
          msg($this->getLang('mess_err_ouvrir')." ($fic).",-1);
          return false;
        }

        if (fwrite($handle, $chaine) === FALSE) {
          msg($this->getLang('mess_err_ecrire')." ($fic).",-1);
          return false;
        }

        fclose($handle);
        return true;

    } else {
      msg($this->getLang('mess_err_lectureseule')." ($fic).",-1);
      return false;
    }
  }
}                                                                                                                                                                  
// vim:ts=4:sw=4:et:enc=utf-8:
