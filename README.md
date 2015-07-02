Door43 DokuWiki Code
====================

This repo is our unified DokuWiki + plugins repo.

To setup:

1) Clone this Door43 repo onto your web server.

2) Make a virtual host that points to the Door43 directory. (If you instead have Door43 as a subdirectory in your htdocs directory, you will need to change the RewriteBase in the .htaccess file and then run this command so it doesn't commit it to the repo: git update-index --assume-unchanged .htaccess)

3) Switch to the development branch: git checkout development

4) Make sure that your web server's process has write access to the conf and data directories and all files and subdirectories.

5) Setup the submodules, such as enhancedindexer and pagequery, by running the following commands:<br/>
git submodule init<br/>
git submodule update

6) Setup your user and acl config files by copying the .dist config files to their regular names:<br/>
cp conf/users.auth.php.dev conf/users.auth.php<br/>
cp conf/acl.auth.php.dev conf/acl.auth.php

7) Put other config files in place (this have different settings on production):
cp conf/local.php.dev conf/local.php
cp conf/plugin.local.php.dev conf/local.php

(The above three steps, 5 thru 7, will soon be replaced with a bootstrap.php file that will set up submodules and config files and content)

8) You can now go to http://&lt;your.door43.domain&gt;/home?do=login and login as admin, password admin.
