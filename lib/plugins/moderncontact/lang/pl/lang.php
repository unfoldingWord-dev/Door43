<?php
/**
 * Polish language file
 *
 * @license GNU General Public License 3 <http://www.gnu.org/licenses/>
 * @author Aleksander Setlak <http://alek.magazynek.org>
 */

// settings must be present and set appropriately for the language
$lang['encoding']   = 'utf-8';
$lang['direction']  = 'ltr';

// for admin plugins, the menu prompt to be displayed in the admin menu
// if set here, the plugin doesn't need to override the getMenuText() method
$lang['menu'] = 'Modern Contact Form';

// custom language strings for the plugin
$lang["field"] = 'Pole';
$lang["value"] = 'Wartość';
$lang["name"] = 'Twoje imię';
$lang["email"] = 'Twój email';
$lang["subject"] = 'Temat';
$lang["content"] = 'Wiadomość';
$lang["contact"] = 'Wyślij';

// error messages
$lang["error"]["unknown"] = 'E-mail nie został wysłany. Skontaktuj się z administratorem.';
$lang["error"]["name"] = 'Wpisz nazwę. Powinna wynosić co najmniej 2 znaki.';
$lang["error"]["email"] = 'Proszę podać adres e-mail. Musi być poprawny.';
$lang["error"]["content"] = 'Proszę wpisać treść wiadomości. Powinna wynosić co najmniej 10 znaków.';
$lang["error"]["captcha"] = 'Wiadomość nie została wysłana. Nie można zweryfikować jako człowieka.';
$lang["error"]["valid_name"] = 'Nieprawidłowy wpis w nazwie.';
$lang["error"]["valid_email"] = 'Nieprawidłowy wpis w adresie e-mail.';
$lang["error"]["valid_subject"] = 'Nieprawidłowy wpis w temacie.';
$lang["error"]["valid_to"] = 'Adres docelowy jest nieprawidłowy.';
$lang["error"]["valid_content"] = 'Nieprawidłowy wpis w polu wiadomości.';
$lang["success"] = 'E-mail został wysłany.';
