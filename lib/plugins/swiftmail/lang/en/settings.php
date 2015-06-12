<?php

$lang['smtp_host'] = 'Your outgoing SMTP server.';
$lang['smtp_port'] = 'The port your SMTP server listens on. Usually 25. 465 for SSL.';
$lang['smtp_ssl']  = 'What kind of encryption is used when communicating with your SMTP Server?'; // off, ssl, tls

$lang['smtp_ssl_o_8']      = 'none';
$lang['smtp_ssl_o_4']   = 'SSL';
$lang['smtp_ssl_o_2']   = 'TLS';

$lang['auth_user'] = 'If authentication is required, put your user name here.';
$lang['auth_pass'] = 'Password for the above user.';
$lang['pop3_host'] = 'If your server uses POP-before-SMTP for authentication, give your POP3 credentials above and put your POP3 server here. For usual SMTP auth leave this field empty.';

$lang['localdomain'] = 'The name to be used during HELO phase of SMTP. Should be the FQDN of the webserver DokuWiki is running on. Leave empty for autodetection.';

$lang['debug'] = 'Print a full error log when sending fails? Disable when everything works!';
