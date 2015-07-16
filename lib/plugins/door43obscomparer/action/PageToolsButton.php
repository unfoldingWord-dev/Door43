<?php
/**
 * Name: PageToolsButton.php
 * Description: A Dokuwiki action plugin to show OBS Comparer button.
 *
 * Author: Richard Mahn
 * Date:   2015-06-01
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
	$door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadPluginBase();

class action_plugin_door43obscomparer_PageToolsButton extends DokuWiki_Action_Plugin {

	/**
	 * Registers a callback function for a given event
	 *
	 * @param Doku_Event_Handler $controller the DokuWiki event controller object
	 * @return void
	 */
	public function register(Doku_Event_Handler $controller) {
		$controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'handle_obs_comparer_action');
	}

	/**
	 * @param Doku_Event $event  event object by reference
	 * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
	 *                           handler was registered]
	 * @return void
	 */
	public function handle_obs_comparer_action(Doku_Event &$event, /** @noinspection PhpUnusedParameterInspection */ $param) {
		if ($event->data !== 'show') return;

		global $INFO;

		$parts = explode(':', strtolower($INFO['id']));

		// If this is an OBS request, the id will have these parts:
		// [0] = language code / namespace
		// [1] = 'obs'
		// [2] = story number '01' - '50'
		if (count($parts) < 2) return;
		if ($parts[1] !== 'obs') return;
		if (! isset($parts[2]) || (preg_match('/^[0-9][0-9]$/', $parts[2]) !== 1)) return;

		$html = file_get_contents(dirname(dirname(__FILE__)) . '/templates/obs_comparer_pagetools.html');

		$html = str_replace('@obsComparerPageToolsText@', 'Compare with Source', $html); // temporary fix

		echo $html; //$this->translateHtml($html, $this->lang); missing this funciton
	}
}
