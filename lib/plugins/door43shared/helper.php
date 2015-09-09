<?php
/**
 * Name: helper.php
 * Description: Methods used by more than one door43 plugin.
 *
 * Author: Phil Hopper
 * Date:   2015-05-20
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

/**
 * Class helper_plugin_door43shared
 */
class helper_plugin_door43shared extends DokuWiki_Plugin {

    /**
     * @var door43Cache
     */
    private static $cache;

    /**
     * Initialize
     */
    function helper_plugin_door43shared() {

        // initialization code
    }

    function loadPluginBase() {
        require_once 'base_classes/plugin_base.php';
    }

    function loadAjaxHelper() {
        require_once 'base_classes/ajax_helper.php';
    }

    function loadActionBase() {
        require_once 'base_classes/action_base.php';
    }

    /**
     * Strings that need translated are delimited by @ symbols. The text between the symbols is the key in lang.php.
     *   Also replaces specific constants that may be used by javascript.
     * @param $html
     * @param array $langArray Normally this would be $this->lang
     * @return mixed
     */
    public function translateHtml($html, $langArray) {

        // remove the initial doc comments
        $html = preg_replace('/^\<!--(.|\n)*--\>(\n)/U', '', $html, 1);

        // replace all strings from the passed $langArray
        $temp = preg_replace_callback('/@(.+?)@/',
            function($matches) use ($langArray) {
                $text = isset($langArray[$matches[1]]) ? $langArray[$matches[1]] : '';
                return (empty($text)) ? $matches[0] : $text;
            }, $html);

        // replace remaining strings from $this->lang
        if (!$this->localised) $this->setupLocale();
        $temp = preg_replace_callback('/@(.+?)@/',
            function($matches) {
                $text = $this->getLang($matches[1]);
                return (empty($text)) ? $matches[0] : $text;
            }, $temp);

        // replace constants
        $temp = str_replace('@DOKU_BASE@', DOKU_BASE, $temp);

        return $temp;
    }

    /**
     * @param string $dir
     */
    public function delete_directory_and_files($dir) {
        foreach(scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            if (is_dir("$dir/$file")) $this->delete_directory_and_files("$dir/$file");
            else unlink("$dir/$file");
        }
        rmdir($dir);
    }

    /**
     * @return door43Cache
     */
    public function getCache() {
        require_once 'cache.php';
        if (empty(self::$cache)) {
            self::$cache = door43Cache::getInstance();
        }
        return self::$cache;
    }

    public function processTemplateFile($fileName) {

        ob_start();

        // Load the template using 'include' so the template can contain PHP code.
        // This was changed to support the auto-complete language selector in templates.
        /** @noinspection PhpIncludeInspection */
        include $fileName;

        $text = ob_get_clean();

        return $text;
    }
}
