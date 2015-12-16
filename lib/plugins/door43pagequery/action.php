<?php
/**
 * Created by IntelliJ IDEA.
 * User: phil
 * Date: 12/15/15
 * Time: 8:27 AM
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadActionBase();
$door43shared->loadAjaxHelper();

class action_plugin_door43pagequery extends Door43_Action_Plugin
{

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller)
    {
        Door43_Ajax_Helper::register_handler($controller, 'get_door43pagequery_async', array($this, 'get_door43pagequery_async'));
    }

    public function get_door43pagequery_async()
    {
        global $INPUT;
        global $conf;

        /** @var syntax_plugin_door43pagequery $syntax */
        $syntax = plugin_load('syntax', 'door43pagequery');

        // get the data object
        $data = $INPUT->param('data');

        // clean up booleans
        foreach($data as $key => $value) {
            if ($value === 'true') {
                $data[$key] = true;
            }
            elseif ($value === 'false') {
                $data[$key] = false;
            }
        }

        $data = array_merge($syntax->_getDefaultOptions(), $data);

        $all_pages = array();
        $all_subnamespaces = array();

        // loop through the requested namespaces
        for ($i = 0; $i < count($data['requested_namespaces']); $i++) {

            $data['wantedNS'] = $data['requested_namespaces'][$i];
            $data['wantedDir'] = $data['requested_directories'][$i];

            //Load lang now rather than at handle-time, otherwise it doesn't
            //behave well with the translation plugin (it seems like we cache strings
            //even if the lang doesn't match)
            $syntax->_denullifyLangOptions($data);

            if (!$this->_isNamespaceUsable($data)) {
                echo sprintf($this->getLang('does_not_exist'), $data['wantedNS']);
                return;
            }

            $fileHelper = new fileHelper($data);
            $pages = $fileHelper->getPages();
            $subnamespaces = $fileHelper->getSubnamespaces();
            if ($this->_shouldPrintPagesAmongNamespaces($data)) {
                $subnamespaces = array_merge($subnamespaces, $pages);
            }

            // process the query if present
            if (!empty($data['query']) && is_array($data['query'])) {
                $pattern = '/' . $data['query'][0] . '/i';
                $matched_pages = array();

                foreach ($pages as $page) {

                    if ($page['type'] != 'f') continue;

                    $fn = $conf['datadir'] . DIRECTORY_SEPARATOR . str_replace(':', DIRECTORY_SEPARATOR, $page['id']) . '.txt';
                    if (is_file($fn)) {

                        $file_contents = file_get_contents($fn);

                        $found = preg_match($pattern, $file_contents);
                        if ($found === 1) {
                            $matched_pages[] = $page;
                        }
                    }
                }

                $pages = $matched_pages;
            }

            $all_subnamespaces = array_merge($all_subnamespaces, $subnamespaces);
            $all_pages = array_merge($all_pages, $pages);
        }

        $html = '';

        if (!empty($all_pages)) {

            // sort the pages
            usort($all_pages, function($a, $b) { return strnatcasecmp($a['sort'], $b['sort']); });

            $style = empty($data['fontsize']) ? '' : 'font-size: ' . $data['fontsize'][0] . ';';

            foreach ($all_pages as $page) {

                $href = '/' . str_replace(':', '/', $page['id']);

                $html .= "<a href=\"{$href}\" class=\"wikilink1\" title=\"{$page['id']}\" style=\"{$style}\">{$page['title']}</a>";

                if ($data['lineBreak']) {
                    $html .= '<br>';
                }

                $html .= DOKU_LF;
            }

            $html .= '<div class="count">' . count($all_pages) . '</div>' . DOKU_LF;
        }

        $html .= '<br class="catpageeofidx">';
        echo $html;
    }


    private function _shouldPrintPagesAmongNamespaces($data){
        return $data['pagesinns'];
    }

    public function _isNamespaceUsable($data){
        global $conf;
        return @opendir($conf['datadir'] . '/' . $data['wantedDir']) !== false && $data['safe'];
    }
}