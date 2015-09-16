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

/**
 * Class action_plugin_door43obsdocupload_ExportButtons
 */
class action_plugin_door43obsdocupload_ExportButtons extends Door43_Action_Plugin {

    private $tempDir;

    /**
     * possible values: -1 = not set, 0 = false, 1 = true
     * @var int
     */
    private $showButton = -1;

    public function __destruct() {

        // cleanup
        if (!empty($this->tempDir)) {

            /* @var $door43shared helper_plugin_door43shared */
            global $door43shared;

            // $door43shared is a global instance, and can be used by any of the door43 plugins
            if (empty($door43shared)) {
                $door43shared = plugin_load('helper', 'door43shared');
            }

            $door43shared->delete_directory_and_files($this->tempDir);
        }
    }

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'load_pagetools_script');
        $controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, 'add_button');
        Door43_Ajax_Helper::register_handler($controller, 'get_obs_doc_export_dlg', array($this, 'get_obs_doc_export_dlg'));
        Door43_Ajax_Helper::register_handler($controller, 'download_obs_template_docx', array($this, 'download_obs_template_docx'));
    }

    /**
     * @return int
     */
    private function showToolstripButton() {

        if ($this->showButton == -1) {

            global $INFO;

            $parts = explode(':', strtolower($INFO['id']));

            // If this is an OBS request, the id will have these parts:
            // [0] = language code / namespace
            // [1] = 'obs'
            // [2] = story number '01' - '50'
            if (count($parts) < 2)
                $this->showButton = 0;
            elseif ($parts[1] !== 'obs')
                $this->showButton = 0;
            elseif (isset($parts[2]) && (preg_match('/^[0-9][0-9]$/', $parts[2]) !== 1))
                $this->showButton = 0;
            else
                $this->showButton = 1;
        }

        return $this->showButton;
    }

    /**
     * This the script for the button in the right-hand tool strip on OBS pages.
     * @param Doku_Event $event  event object by reference
     * @param mixed $param  [the parameters passed as fifth argument to register_hook() when this handler was registered]
     * @return void
     */
    public function load_pagetools_script(Doku_Event &$event, /** @noinspection PhpUnusedParameterInspection */ $param) {

        if ($event->data !== 'show') return;

        if ($this->showToolstripButton() !== 1) return;

        $html = file_get_contents(dirname(dirname(__FILE__)) . '/templates/obs_export_script.html');

        echo $this->translateHtml($html);
    }

    /**
     * Add 'Get template' button to the right-hand tool strip on OBS pages.
     *
     * @param Doku_Event $event
     */
    public function add_button(Doku_Event $event) {

        if ($this->showToolstripButton() !== 1) return;

        // export button
        $btn = '<li id="getObsTemplateBtn"><a href="#" class=" tx-export" rel="nofollow" ><span>' . $this->getLang('getTemplate') . '</span></a></li>';
        $event->data['items']['export_obs_template'] = $btn;

        //// import button
        //$btn = '<li id="importObsDocxBtn"><a href="#" class=" tx-import" rel="nofollow" ><span>' . $this->getLang('importDocx') . '</span></a></li>';
        //$event->data['items']['import_obs_docx'] = $btn;
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

    /**
     * @param string $url
     * @return string
     */
    private function get_image_file_from_url($url) {

        // URL for hyperlinks: https://api.unfoldingword.org/obs/jpg/1/en/360px/obs-en-01-01.jpg
        // Location on disk: /var/www/vhosts/api.unfoldingword.org/httpdocs/obs/jpg/1/en/360px/obs-en-01-01.jpg
        $file_name = str_replace('https://api.unfoldingword.org/obs/',
            '/var/www/vhosts/api.unfoldingword.org/httpdocs/obs/',
            $url);

        return (is_file($file_name)) ? $file_name : '';
    }

    public function download_obs_template_docx() {

        global $INPUT;
        $langCode = $INPUT->str('lang');
        $includeImages = $INPUT->bool('img');
        $draft = $INPUT->bool('draft');

        if ($draft && ($langCode != 'en')) {

            // get the metadata
            $metaData = array('version' => 'DRAFT');

            // get the obs data
            $obs = $this->get_draft_obs($langCode);

            // get the front matter
            $url = "https://api.unfoldingword.org/obs/txt/1/en/obs-en-front-matter.json";
            $raw = file_get_contents($url);
            $frontMatter = json_decode($raw, true);
        }
        else {

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
        }


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

        $tempDir = $this->get_temp_dir();
        $markdownFile = $tempDir . DIRECTORY_SEPARATOR . 'obs.md';
        file_put_contents($markdownFile, $markdown);

        // convert to docx with pandoc
        $docxFile = $tempDir . DIRECTORY_SEPARATOR . 'obs.docx';
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
    }

    /**
     * @return string
     */
    private function get_temp_dir() {

        if (empty($this->tempDir)) {

            $increment = 0;
            $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'obs2docx';

            while (is_dir($dir . DIRECTORY_SEPARATOR . $increment)) {
                $increment++;
            }

            $dir = $dir . DIRECTORY_SEPARATOR . $increment;
            mkdir($dir, 0755, true);

            $this->tempDir = $dir;
        }

        return $this->tempDir;
    }

    /**
     * @param string $langCode The namespace to use as the source
     * @return array
     */
    private function get_draft_obs($langCode) {

        global $conf;

        $obs = array('chapters' => array());

        $pagesDir = $conf['datadir'];
        $srcDir = $pagesDir . DS . $langCode . DS . 'obs';

        for ($story_num = 1; $story_num < 51; $story_num++) {

            $file = $srcDir . DS . str_pad($story_num, 2, '0', STR_PAD_LEFT) . '.txt';

            $srcText = file_get_contents($file);
            $parts = array_values(array_filter(explode("\n", $srcText)));

            // build the chapter
            $chapNum = substr(basename($file), 0, -4);
            $chapter = array('number' => $chapNum);
            $chapter['title'] = trim($parts[0], "= \t\n\r\0\x0B");
            $chapter['ref'] = trim($parts[count($parts) - 1], "/ \t\n\r\0\x0B");
            $chapter['frames'] = array();

            // frames
            $frameNum = 1;
            for ($i=1; $i < (count($parts) - 1); $i = $i+2) {

                $frame = array('id' => $chapNum . '-' . str_pad($frameNum++, 2, '0', STR_PAD_LEFT));
                $frame['img'] = trim($parts[$i], "{} \t\n\r\0\x0B");
                $frame['text'] = $parts[$i + 1];
                $chapter['frames'][] = $frame;
            }
            $obs['chapters'][] = $chapter;
        }


        return $obs;
    }
}
