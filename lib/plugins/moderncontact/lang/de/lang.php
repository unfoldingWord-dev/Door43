<?php
/**
 * German language file
 *
 * @license GNU General Public License 3 <http://www.gnu.org/licenses/>
 * @author Wolfgang Studer
 * @author Marvin Thomas Rabe <mrabe@marvinrabe.de>
 */

// settings must be present and set appropriately for the language
$lang['encoding']   = 'utf-8';
$lang['direction']  = 'ltr';

// for admin plugins, the menu prompt to be displayed in the admin menu
// if set here, the plugin doesn't need to override the getMenuText() method
$lang['menu'] = 'Modernes Kontaktformular';

// custom language strings for the plugin
$lang["field"] = 'Feld';
$lang["value"] = 'Wert';
$lang["name"] = 'Dein Name';
$lang["email"] = 'Deine E-Mail';
$lang["subject"] = 'Betreff';
$lang["content"] = 'Nachricht';
$lang["contact"] = 'Senden';

// error messages
$lang["error"]["unknown"] = 'E-Mail nicht gesendet. Bitte Administrator kontaktieren.';
$lang["error"]["name"] = 'Bitte Name angeben. Sollte mindestens aus 2 Zeichen bestehen.';
$lang["error"]["email"] = 'Bitte valide E-Mail Adresse eingeben.';
$lang["error"]["content"] = 'Bitte Nachricht aus mindestens 10 Zeichen eingeben.';
$lang["error"]["captcha"] = 'E-Mail wurde nicht gesendet. Konnte dich nicht als Mensch identifizieren.';
$lang["error"]["valid_name"] = 'Name enthält eine ungültige eingabe.';
$lang["error"]["valid_email"] = 'E-mail Adresse enthält eine ungültige eingabe.';
$lang["error"]["valid_subject"] = 'Betreff enthält eine ungültige eingabe.';
$lang["error"]["valid_to"] = 'Ziel Adresse enthält eine ungültige eingabe.';
$lang["error"]["valid_content"] = 'Nachricht enthält eine ungültige eingabe.';
$lang["success"] = 'Nachricht erfolgreich verschickt.';
