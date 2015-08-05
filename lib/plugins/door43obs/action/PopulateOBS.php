<?php
/**
 * Name: PopulateOBS.php
 * Description: A Dokuwiki action plugin to handle the  button click.
 *
 * Author: Phil Hopper
 * Date:   2014-12-10
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadAjaxHelper();

class action_plugin_door43obs_PopulateOBS extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'create_obs_now', array($this, 'initialize_obs_content'));
    }

    public function initialize_obs_content() {

        global $conf;
        global $INPUT;

        header('Content-Type: text/plain');

        // get the iso codes for the source and destination languages
        $srcLangCode = $INPUT->str('sourceLang');
        $dstLangCode = $this->get_lang_code_from_language_name_string($INPUT->str('destinationLang'));

        // check if the destination namespace exists
        $pagesDir = $conf['datadir'];
        $dstNamespaceDir = $pagesDir . DS . $dstLangCode;
        if (!$this->check_namespace($dstNamespaceDir)) {

            // if not found, report an error
            echo sprintf($this->get_error_message('obsNamespaceNotFound'), $dstLangCode);
            return;
        }

        // check if the source obs directory exists
        $srcDir = $pagesDir . DS . $srcLangCode . DS . 'obs';
        if (!is_dir($srcDir)) {

            // if not found, report an error
            echo sprintf($this->get_error_message('obsSourceDirNotFound'), $srcLangCode);
            return;
        }

        // check if the destination obs directory already exists
        $dstDir = $dstNamespaceDir . DS . 'obs';
        if (is_dir($dstDir)) {

            // if the directory exists, are there txt files in it?
            $files = glob($dstDir . DS . '*.txt', GLOB_NOSORT);
            if (!empty($files) && (count($files) > 5)) {

                // if there are, report an error
                echo sprintf($this->get_success_message('obsDestinationDirExists'), "/$dstLangCode/obs", "$dstLangCode/obs");
                return;
            }
        }

        // some files will come from the templates directory
        $templateDir = $pagesDir . '/templates/obs3/obs';

        // Now copy the obs files from $srcDir to $dstDir
        $this->copy_obs_files($srcDir, $dstDir, $templateDir, $srcLangCode, $dstLangCode);

        // update home.txt
        $templateDir = $pagesDir . '/templates';
        $this->update_home_txt($templateDir, $dstNamespaceDir, $dstLangCode);

        // update sidebar.txt
        $this->update_sidebar_txt($templateDir, $dstNamespaceDir, $dstLangCode);

        // make uwadmin status page
        $adminDir = $pagesDir . "/en/uwadmin";
        $this->copy_status_txt($templateDir, $adminDir, $dstLangCode);

        // skip this section during unit testing
        if(!defined('DOKU_UNITTEST')) {

            // update changes pages
            $script = '/var/www/vhosts/door43.org/tools/obs/dokuwiki/obs-gen-changes-pages.sh';
            if (is_file($script))
                shell_exec($script);

            // git add, commit, push
            $this->git_push($adminDir, 'Added uwadmin obs page for ' . $dstLangCode);
            $this->git_push($dstNamespaceDir, 'Initial import of OBS');
        }

        echo sprintf($this->get_success_message('obsCreatedSuccess'), $dstLangCode, "/$dstLangCode/obs");
    }

    private function get_error_message($langStringKey) {
        return '<span style="color: #990000;">' . $this->getLang($langStringKey) . '</span><br>';
    }

    public function get_success_message($langStringKey) {
        return '<span style="color: #005500;">' . $this->getLang($langStringKey) . '</span><br>';
    }

    private function get_lang_code_from_language_name_string($languageName) {

        // extract iso code from the destination language field, i.e.: "English (en)"
        $pattern = '/\([^\(\)]+\)$/';
        $matches = array();
        if (preg_match($pattern, $languageName, $matches) === 1)
            return preg_replace('/\(|\)/', '', $matches[0]);

        // if no matches, hopefully $languageName is the iso
        return $languageName;
    }

    /**
     * Check if a namespace exists.
     * @param $namespaceDir
     * @return bool
     */
    private function check_namespace($namespaceDir) {
        return is_dir($namespaceDir);
    }

    private function copy_obs_files($srcDir, $dstDir, $templateDir, $srcLangCode, $dstLangCode) {

        if (!is_dir($dstDir))
            mkdir($dstDir, 0755, true);

        // create the 01.txt through 50.txt source files
        $this->create_files_from_json($srcLangCode, $dstDir);

        // copy some files from source directory
        $files = array('back-matter.txt', 'front-matter.txt', 'cover-matter.txt');
        foreach($files as $file) {

            $srcFile = $srcDir . DS . $file;
            if (!is_file($srcFile)) continue;

            $outFile = $dstDir . DS . $file;
            copy($srcFile, $outFile);
            chmod($outFile, 0644);
        }

        // copy these files from /templates/obs3/obs
        $files = array('sidebar.txt', 'stories.txt');
        foreach($files as $file) {

            $srcFile = $templateDir . DS . $file;
            $outFile = $dstDir . DS . $file;
            $this->copy_template_file($srcFile, $outFile, $dstLangCode);
        }

        // create the obs.txt home page
        $srcFile = dirname($templateDir) . DS . 'obs.txt';
        $outFile = dirname($dstDir) . DS . 'obs.txt';
        $this->copy_template_file($srcFile, $outFile, $dstLangCode);
    }

    private function create_files_from_json($srcLangCode, $dstDir) {

        $src = file_get_contents("https://api.unfoldingword.org/obs/txt/1/{$srcLangCode}/obs-{$srcLangCode}.json");
        $srcClass = json_decode($src, true);

        // chapters
        //   frames
        //     id: "01-01"
        //     img: "url"
        //     text: "frame text"
        //   number: "01",
        //   ref: "A Bible story from: Genesis 1-2",
        //   title: "1. The Creation"
        foreach($srcClass['chapters'] as $chapter) {

            $outFile = $dstDir . DS . $chapter['number'] . '.txt';

            $text = "====== {$chapter['title']} ======\n\n";

            foreach($chapter['frames'] as $frame) {
                $text .= $this->add_frame($frame['img'], $frame['text']);
            }

            $text .= "//{$chapter['ref']}//\n\n\n";

            file_put_contents($outFile, $text);
            chmod($outFile, 0644);
        }

        // app_words
        $outFile = $dstDir . DS . 'app_words.txt';
        $text = "//Translation for the unfoldingWord mobile app interface//\n";

        foreach($srcClass['app_words'] as $key => $value) {
            $text .= "\n\n{$key}: {$value}\n";
        }

        file_put_contents($outFile, $text);
        chmod($outFile, 0644);
    }

    private function add_frame($imgUrl, $text) {

        // the image
        $returnVal = "\n{{" . $imgUrl . "}}\n\n";

        // the text
        $returnVal .= "\n{$text}\n\n";

        // leave room for the translation
        $returnVal .= "\n\n";

        return $returnVal;
    }

    private function copy_template_file($srcFile, $outFile, $dstLangCode) {

        $text = file_get_contents($srcFile);
        file_put_contents($outFile, str_replace('LANGCODE', $dstLangCode, $text));
        chmod($outFile, 0644);
    }

    private function update_home_txt($templateDir, $dstNamespaceDir, $dstLangCode) {

        $homeFile = $dstNamespaceDir . DS . 'home.txt';
        if (!is_file($homeFile)) {

            $srcFile = $templateDir . DS . 'home.txt';
            $this->copy_template_file($srcFile, $homeFile, $dstLangCode);
        }

        $text = file_get_contents($homeFile);
        $text .= "\n===== Resources =====\n\n  * **[[{$dstLangCode}:obs|Open Bible Stories ({$dstLangCode})]]**";
        file_put_contents($homeFile, $text);
    }

    private function update_sidebar_txt($templateDir, $dstNamespaceDir, $dstLangCode) {

        $sidebarFile = $dstNamespaceDir . DS . 'sidebar.txt';
        if (!is_file($sidebarFile)) {

            $srcFile = $templateDir . DS . 'sidebar.txt';
            $this->copy_template_file($srcFile, $sidebarFile, $dstLangCode);
        }

        $text = file_get_contents($sidebarFile);
        $text .= "\n**Resources**\n\n  * [[{$dstLangCode}:obs|Open Bible Stories ({$dstLangCode})]]\n\n**Latest OBS Status**\n{{page>en:uwadmin:{$dstLangCode}:obs:status}}";
        file_put_contents($sidebarFile, $text);
    }

    private function copy_status_txt($templateDir, $adminDir, $dstLangCode) {

        $adminDir .= "/{$dstLangCode}/obs";
        if (!is_dir($adminDir)) mkdir($adminDir, 0755, true);

        $statusFile = $adminDir . DS . 'status.txt';
        $srcFile = $templateDir . DS . 'status.txt';

        $text = file_get_contents($srcFile);
        $text = str_replace('ORIGDATE', date('Y-m-d'), $text);

        file_put_contents($statusFile, $text);
    }

    private function git_push($dir, $msg) {

        $originalDir = getcwd();

        chdir($dir);

        // the 2>&1 redirect sends errorOut to stdOut
        $result1 = shell_exec('git add . 2>&1');
        $result2 = shell_exec('git commit -am "' . $msg . '" 2>&1');
        $result3 = shell_exec('git push origin master 2>&1');

        // show the git output in a development environment
        if (($_SERVER['SERVER_NAME'] == 'localhost') || ($_SERVER['SERVER_NAME'] == 'test.door43.org'))
            echo "<br>Git Response: $result1<br><br>Git Response: $result2<br><br>Git Response: $result3<br><br>";

        chdir($originalDir);
    }
}


