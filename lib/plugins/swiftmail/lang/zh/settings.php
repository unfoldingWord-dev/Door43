<?php

$lang['smtp_host'] = '您的 SMTP 发送服务器。';
$lang['smtp_port'] = '您的 SMTP 服务器监听端口。通常是 25，对于 SSL常是 465。';
$lang['smtp_ssl']  = '您的 SMTP 服务器所用的加密类型？'; // off, ssl, tls

$lang['smtp_ssl_o_8']   = '无';
$lang['smtp_ssl_o_4']   = 'SSL';
$lang['smtp_ssl_o_2']   = 'TLS';

$lang['auth_user'] = '如果需要认证，在这里输入您的用户名。';
$lang['auth_pass'] = '对应上面用户名的密码。';
$lang['pop3_host'] = '如果您的服务器使用 POP-before-SMTP 认证，在上面给出您的 POP3 认证信息，并在这里填入您的 POP3 服务器。对于通常的 SMTP 认证，本栏留空。';

$lang['localdomain'] = '在 SMTP 握手阶段所使用的名称。应为运行 Dokuwiki 服务器的完全资格域名。留空则自动检测。';

$lang['debug'] = '在发送失败时显示完整的错误信息？在一切正常时请关闭！';
