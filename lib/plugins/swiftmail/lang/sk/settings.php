<?php
/**
 * Slovak language for the "swiftmail" DokuWiki plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Martin Hanula <mh.ikar@gmail.com>
 */

$lang['smtp_host']    = 'Meno/adresa SMTP servera pre odosielanie pošty';       // eng: 'Your outgoing SMTP server.';
$lang['smtp_port']    = 'Číslo portu, na ktorom počúva SMTP server. Väčšinou 25, resp. 465 pre SSL.'; // eng: 'The port your SMTP server listens on. Usually 25. 465 for SSL.';
$lang['smtp_ssl']     = 'Typ šifrovania, ktorý sa používa pri komunikácii s SMTP serverom'; // eng: 'What kind of encryption is used when communicating with your SMTP Server?'; // off, ssl, tls

$lang['smtp_ssl_o_8'] = 'žiadne';                                               // eng: 'none';
$lang['smtp_ssl_o_4'] = 'SSL';                                                  // eng: 'SSL';
$lang['smtp_ssl_o_2'] = 'TLS';                                                  // eng: 'TLS';

$lang['auth_user']    = 'Ak SMTP server vyžaduje autentifikáciu, použiť nasledovné meno používateľa';  // eng: 'If authentication is required, put your user name here.';
$lang['auth_pass']    = 'Ak SMTP server vyžaduje autentifikáciu, použiť nasledovné heslo používateľa'; // eng: 'Password for the above user.';
$lang['pop3_host']    = 'Ak SMTP server používa POP-before-SMTP na autentifikáciu, použiť horeuvedené meno/heslo a nasledovný POP3 server na autetifikáciu. Ak SMTP server používa bežnejší SMTP AUTH, nechať pole prázdne!'; // eng: 'If your server uses POP-before-SMTP for authentication, give your POP3 credentials above and put your POP3 server here. For usual SMTP auth leave this field empty.';

$lang['localdomain']  = 'Meno použité v HELO fáze SMTP. Malo by to byť FQDN webservera, na ktorom beží DokuWiki. Pre autodetekciu nechať pole prázdne.';  // eng: 'The name to be used during HELO phase of SMTP. Should be the FQDN of the webserver DokuWiki is running on. Leave empty for autodetection.';

$lang['debug']        = 'Zobraziť celý chybový log keď odosielanie zlyhá? <b>Deaktivovať, ak všetko funguje!</b>'; // eng: 'Print a full error log when sending fails? Disable when everything works!';
