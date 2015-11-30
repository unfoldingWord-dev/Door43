<?php
/**
 * Fichier de traduction en français
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Bob Baddeley <bob@bobbaddeley.com>
 * @author     Arnaud Fouquaut <afouquaut@no-log.org>
 */

// settings must be present and set appropriately for the language
$lang['encoding']   = 'utf-8';
$lang['direction']  = 'ltr';

// for admin plugins, the menu prompt to be displayed in the admin menu
// if set here, the plugin doesn't need to override the getMenuText() method
$lang['menu'] = 'Formulaire de contact';

// custom language strings for the plugin
$lang["field"] = 'Champ';
$lang["value"] = 'Valeur';
$lang["name"] = 'Votre nom';
$lang["email"] = 'Votre adresse électronique';
$lang["subject"] = 'Sujet';
$lang["content"] = 'Message';
$lang["contact"] = 'Envoyer';

// error messages

$lang["error"]["unknown"] = 'Email non envoyé. Merci de contacter votre administrateur.'; 
$lang["error"]["name"] = 'Merci de saisir votre nom. Celui-ci doit être composé d’au moins 2 caractères.';
$lang["error"]["email"] = 'Merci de saisir votre adresse email. Attention celle-ci doit être valide.';
$lang["error"]["content"] = 'Merci d’ajouter un message d’au moins 10 caractères.';
$lang["error"]["captcha"] = 'Email non envoyé. Vous n’avez pas été vérifié comme étant un humain.';
$lang["error"]["valid_name"] = 'Votre nom n’est pas valide.';
$lang["error"]["valid_email"] = 'Votre adresse email n’est pas valide.';
$lang["error"]["valid_subject"] = 'Le sujet n’est pas valide.';
$lang["error"]["valid_to"] = 'L’adresse de destination n’est pas valide.';
$lang["error"]["valid_content"] = 'Le message n’est pas valide.';
$lang["success"] = 'L’email a été envoyé avec succès.';
