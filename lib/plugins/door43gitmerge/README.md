door43gitmerge Plugin for DokuWiki

This tool provides a way to merge newly submitted translations from tS.

All documentation for this plugin can be found at
https://github.com/Door43/dokuwiki-git-merge

If you install this plugin manually, make sure it is installed in
lib/plugins/door43gitmerge/ - if the folder is called different it
will not work!

Please refer to http://www.dokuwiki.org/plugins for additional info
on how to install plugins in DokuWiki.

##Settings
For this plugin to properly function, you must set the path to the
Git repositories so the plugin can access them. This can be configured
in the DokuWiki Configuration Manager:

    http://YOUR-DOKUWIKI-SITE.COM/?do=admin&page=config#plugin____door43gitmerge____plugin_settings_name

Replace `http://YOUR-DOKUWIKI-SITE.COM` with your site's domain.

##Hooks
The git hooks in the `hooks` directory must be copied to the appropriate location on the server. These hooks will keep the plugin functioning properly without having to manually run the indexing.

##Indexing
After the plugin is correctly configured visit http://test.door43.org/en/obs/01?do=door43gitmerge-crawl (update the domain name as appropriate) to trigger the initial indexing of the repositories. 

> Note: To make sure no possible merge updates are missing, the crawl should be triggered after the git hooks are put into place.