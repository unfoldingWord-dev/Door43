<?php
/*
 * Local plugin enable/disable settings
 * Auto-generated through plugin/extension manager
 *
 * NOTE: Plugins will not be added to this file unless there is a need to override a default setting. Plugins are
 *       enabled by default, unless having a 'disabled' file in their plugin folder.
 */
$plugins['door43obsreview'] = 0;
$plugins['gitcommit'] = 0;
$plugins['piwik2'] = 0;

// end auto-generated content

// load a local_private.php and overwrite above properties if they exist in the private file. Added by: Richard Mahn
if(file_exists(dirname(__FILE__).'/plugins.local_private.php')) {
    require(dirname(__FILE__).'/plugins.local_private.php');
}
