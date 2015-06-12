<?php
/**
 * DokuWiki plugin for piwik2
 *
 * Generates the inserted js/html code that is inserted into dom (HTML-HEAD)
 *
 * @license GPLv3 (http://www.gnu.org/licenses/gpl.html)
 * @author Marcel Lange <info@aasgard.de>
 */
require_once(DOKU_INC . 'inc/auth.php');


/**
 * Injects the necessary trackingcodes for piwik tracking (v2.x) into DOM
 * like specified in the plugin manager fields
 */
function piwik_code()
{
	global $conf;

	if (isset($conf['plugin']['piwik2']['js_tracking_code'])
        || (isset($conf['plugin']['piwik2']['img_tracking_code']))
    ) {
		// Config does not contain keys if they are default;
		// so check whether they are set & to non-default value
		
		// default 0, so check if it's not set or 0
		if (!isset($conf['plugin']['piwik2']['track_admin_user']) || $conf['plugin']['piwik2']['track_admin_user'] == 0) {
			if (isset($_SERVER['REMOTE_USER']) && auth_isadmin()) { return; }
		}
		
		// default 1, so check if it's set and 0
		if (isset($conf['plugin']['piwik2']['track_user']) && $conf['plugin']['piwik2']['track_user'] == 0) {
			if (isset($_SERVER['REMOTE_USER'])) { return; }
		}

        //changes made by Marcel Lange (info@bravehartk2.de)
        $trackingCode = (isset($conf['plugin']['piwik2']['js_tracking_code']))? $conf['plugin']['piwik2']['js_tracking_code'] : '';
        if(isset($conf['plugin']['piwik2']['use_img_tracking']) && $conf['plugin']['piwik2']['use_img_tracking'] == 1 && isset($conf['plugin']['piwik2']['img_tracking_code'])){
            $trackingCode = $conf['plugin']['piwik2']['img_tracking_code'];
        }
        ptln($trackingCode);
	} else {
		// Show configuration tip for admin
		if (isset($_SERVER['REMOTE_USER']) && auth_isadmin()) {
			msg('Please configure the piwik2 plugin');
		}
	}
}