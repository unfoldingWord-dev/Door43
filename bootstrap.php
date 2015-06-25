<?php

`cp conf/local.php.dev conf/local.php`;
`cp conf/plugin.local.php.dev conf/plugin.local.php`;
`cp conf/acl.auth.php.dev conf/acl.auth.php`;
`cp conf/users.auth.php.dev conf/users.auth.php`;

`git config core.fileMode false`;

`git submodule init`;
`git submodule update`;

echo 'OK! Proceed to <a href="home">Home page</a>';
