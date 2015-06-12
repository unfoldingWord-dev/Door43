<?php
/**
 * DokuWiki plugin for Piwik2
 *
 * Hook into application -> executed after header metadata was rendered
 *
 * @license GPLv3 (http://www.gnu.org/licenses/gpl.html)
 * @author Marcel Lange <info@aasgard.de>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once DOKU_PLUGIN.'piwik2/code.php';

class action_plugin_piwik2 extends DokuWiki_Action_Plugin {
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hook_header');
	}

	function _hook_header(&$event, $param) {
		$data = piwik_code();
		$event->data['script'][] = array(
			'type' => 'text/javascript',
			'charset' => 'utf-8',
			'_data' => $data,
		);
	}
}
