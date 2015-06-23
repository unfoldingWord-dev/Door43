<?php
/**
 * Name: ExportButtons.php
 * Description: A Dokuwiki action plugin to show OBS export buttons.
 *
 * Author: Phil Hopper
 * Date:   2015-05-23
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadActionBase();
$door43shared->loadAjaxHelper();

class action_plugin_door43obsdocupload_ExportButtons extends Door43_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'handle_obs_action');
        Door43_Ajax_Helper::register_handler($controller, 'get_obs_doc_export_dlg', array($this, 'get_obs_doc_export_dlg'));
        Door43_Ajax_Helper::register_handler($controller, 'download_obs_template_docx', array($this, 'download_obs_template_docx'));
    }

    /**
     * This adds a button to the right-hand tool strip on OBS pages.
     * @param Doku_Event $event  event object by reference
     * @param mixed $param  [the parameters passed as fifth argument to register_hook() when this handler was registered]
     * @return void
     */
    public function handle_obs_action(Doku_Event &$event, /** @noinspection PhpUnusedParameterInspection */ $param) {

        if ($event->data !== 'show') return;

        global $INFO;

        $parts = explode(':', strtolower($INFO['id']));

        // If this is an OBS request, the id will have these parts:
        // [0] = language code / namespace
        // [1] = 'obs'
        // [2] = story number '01' - '50'
        if (count($parts) < 2) return;
        if ($parts[1] !== 'obs') return;
        if (isset($parts[2]) && (preg_match('/^[0-9][0-9]$/', $parts[2]) !== 1)) return;

        $html = file_get_contents(dirname(dirname(__FILE__)) . '/templates/obs_export_buttons.html');

        echo $this->translateHtml($html, $this->lang);
    }

    public function get_obs_doc_export_dlg() {

        /* @var $door43shared helper_plugin_door43shared */
        global $door43shared;

        // $door43shared is a global instance, and can be used by any of the door43 plugins
        if (empty($door43shared)) {
            $door43shared = plugin_load('helper', 'door43shared');
        }

        $html = file_get_contents($this->root . '/templates/obs_export_dlg.html');
        if (!$this->localised) $this->setupLocale();
        $html = $door43shared->translateHtml($html, $this->lang);

        echo $html;
    }

    private function get_image_file_from_url($url) {

        // https://api.unfoldingword.org/obs/jpg/1/en/360px/obs-en-01-01.jpg
        // /var/www/vhosts/api.unfoldingword.org/httpdocs/obs/jpg/1/en/360px/obs-en-01-01.jpg
        $file_name = str_replace('https://api.unfoldingword.org/obs/',
                                 '/var/www/vhosts/api.unfoldingword.org/httpdocs/obs/',
                                 $url);

        return (is_file($file_name)) ? $file_name : '';
    }

    public function download_obs_template_docx() {

        global $INPUT;
        $langCode = $INPUT->str('lang');
        $includeImages = $INPUT->bool('img');

        // get the metadata
        $url = "https://api.unfoldingword.org/obs/txt/1/{$langCode}/status-{$langCode}.json";
        $raw = file_get_contents($url);
        $metaData = json_decode($raw, true);

        // get the obs data
        $url = "https://api.unfoldingword.org/obs/txt/1/{$langCode}/obs-{$langCode}.json";
        $raw = file_get_contents($url);
        $obs = json_decode($raw, true);

        // get the front matter
        $url = "https://api.unfoldingword.org/obs/txt/1/{$langCode}/obs-{$langCode}-front-matter.json";
        $raw = file_get_contents($url);
        if (($raw === false) && ($langCode != 'en')) {
            $url = "https://api.unfoldingword.org/obs/txt/1/en/obs-en-front-matter.json";
            $raw = file_get_contents($url);
        }
        $frontMatter = json_decode($raw, true);

        // now put it all together
        $markdown = $frontMatter['name'] . "\n";
        $markdown .= str_repeat('=', strlen($frontMatter['name'])) . "\n\n";
        $markdown .= $frontMatter['front-matter'] . "\n\n";
        $markdown .= "-----\n\n";

        // get the images, download if requested to be included
        $images = array();
        foreach ($obs['chapters'] as $chapter) {

            foreach ($chapter['frames'] as $frame) {

                if ($includeImages) {
                    $image_file = $this->get_image_file_from_url($frame['img']);
                    $images[$frame['id']] = "![{$frame['id']}]({$image_file})\\";
                }
                else {
                    $images[$frame['id']] = "[{$frame['img']}]({$frame['img']})";
                }
            }
        }

        foreach ($obs['chapters'] as $chapter) {

            $markdown .= $chapter['title'] . "\n";
            $markdown .= str_repeat('=', strlen($chapter['title'])) . "\n\n";

            foreach ($chapter['frames'] as $frame) {
                $markdown .= "{$images[$frame['id']]}\n\n";
                $markdown .= "{$frame['text']}\n\n";
            }

            $markdown .= "*{$chapter['ref']}*\n\n";
            $markdown .= "-----\n\n";
        }

        // create the temp markdown file
        $increment = 0;
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'obs2docx';

        while (is_dir($tempDir . DIRECTORY_SEPARATOR . $increment)) {
            $increment++;
        }

        $tempDir = $tempDir . DIRECTORY_SEPARATOR . $increment;
        mkdir($tempDir, 0755, true);

        $markdownFile = $tempDir . DIRECTORY_SEPARATOR . 'obs.md';
        $docxFile = $tempDir . DIRECTORY_SEPARATOR . 'obs.docx';
        file_put_contents($markdownFile, $markdown);

        // convert to docx with pandoc
        $cmd = "/usr/bin/pandoc \"$markdownFile\" -s -f markdown -t docx  -o \"$docxFile\"";
        exec($cmd, $output, $error);

        // send to the browser
        if (is_file($docxFile)) {

            $saveAsName = 'obs_' . $langCode . '_v' . preg_replace('/(\s+|\.+)+/', '-', $metaData['version']) . '_' . date('Y-m-d') . '.docx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document; charset=utf-8');
            header('Content-Length: ' . filesize($docxFile));
            header('Content-Disposition: attachment; filename="' . $saveAsName . '"');

            readfile($docxFile);
        }
        else {
            if (!$this->localised) $this->setupLocale();
            header('Content-Type: text/plain');
            echo $this->getLang('docxFileCreateError');
        }

        // cleanup
        /* @var $door43shared helper_plugin_door43shared */
        global $door43shared;

        // $door43shared is a global instance, and can be used by any of the door43 plugins
        if (empty($door43shared)) {
            $door43shared = plugin_load('helper', 'door43shared');
        }

        $door43shared->delete_directory_and_files($tempDir);
    }
}
