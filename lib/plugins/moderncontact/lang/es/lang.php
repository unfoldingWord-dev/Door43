<?php
/**
 * Spanish language file
 *
 * @license GNU General Public License 3 <http://www.gnu.org/licenses/>
 * @author Cristian Wente <cristian@wente.dk>
 */

// settings must be present and set appropriately for the language
$lang['encoding']   = 'utf-8';
$lang['direction']  = 'ltr';

// for admin plugins, the menu prompt to be displayed in the admin menu
// if set here, the plugin doesn't need to override the getMenuText() method
$lang['menu'] = 'Formulario moderno de contacto';

// custom language strings for the plugin
$lang["field"] = 'Casilla';
$lang["value"] = 'Valor';
$lang["name"] = 'Su nombre';
$lang["email"] = 'Su dirección de correo electrónico';
$lang["subject"] = 'Asunto';
$lang["content"] = 'Mensaje';
$lang["contact"] = 'Enviar';

// error messages
$lang["error"]["unknown"] = 'El mensaje no fue enviado. Por favor enviar un mensaje al administrator.';
$lang["error"]["name"] = 'Por favor inserte su nombre. Debe ser por lo menos 2 letras.';
$lang["error"]["email"] = 'Por favor inserte su dirección de correo electrónico. Esta debe ser valida.';
$lang["error"]["content"] = 'Por favor inserte un mensaje. Este debe ser por lo menos 10 letras.';
$lang["error"]["captcha"] = 'Mensaje no enviado. No se pudo verificar que usted es humano.';
$lang["error"]["valid_name"] = 'El nombre esta invalido.';
$lang["error"]["valid_email"] = 'La dirección de correo electrónico esta invalida.';
$lang["error"]["valid_subject"] = 'El Asunto esta invalido.';
$lang["error"]["valid_to"] = 'La dirección del destinario es invalida.';
$lang["error"]["valid_content"] = 'El mensaje esta invalido.';
$lang["success"] = 'El mensaje fue enviado con exito.';
