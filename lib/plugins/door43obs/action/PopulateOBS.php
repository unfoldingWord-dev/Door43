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

require_once dirname(dirname(__FILE__)) . '/private/obs_ajax_results.php';

// $door43shared is a global instance, and can be used by any of the door43 plugins
if (empty($door43shared)) {
    $door43shared = plugin_load('helper', 'door43shared');
}

/* @var $door43shared helper_plugin_door43shared */
$door43shared->loadAjaxHelper();

/**
 * Class action_plugin_door43obs_PopulateOBS
 */
class action_plugin_door43obs_PopulateOBS extends DokuWiki_Action_Plugin {

    private $srcLangCode;
    private $dstLangCode;
    private $pagesDir;
    private $srcDir;
    private $dstDir;
    private $dstNamespaceDir;
    private $srcNamespaceDir;
    
    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'create_obs_now', array($this, 'initialize_obs_content'));
        Door43_Ajax_Helper::register_handler($controller, 'create_obs_notes', array($this, 'initialize_obs_notes'));
        Door43_Ajax_Helper::register_handler($controller, 'create_obs_words', array($this, 'initialize_obs_words'));
        Door43_Ajax_Helper::register_handler($controller, 'create_obs_questions', array($this, 'initialize_obs_questions'));
    }

    /**
     * @param string $desiredSubDirectory The sub-directory of the namespaces from which and to which files will be copied
     */
    private function prepare_for_initialization($desiredSubDirectory) {

        global $conf;
        global $INPUT;

        // get the iso codes for the source and destination languages
        $this->srcLangCode = $INPUT->str('sourceLang');
        $this->dstLangCode = self::get_lang_code_from_language_name_string($INPUT->str('destinationLang'));

        // check if the destination namespace exists
        $this->pagesDir = $conf['datadir'];
        $this->dstNamespaceDir = $this->pagesDir . DS . $this->dstLangCode;
        if (!$this->check_namespace($this->dstNamespaceDir)) {

            // if not found, report an error
            self::return_this('Not Found',  sprintf($this->getLang('obsNamespaceNotFound'), $this->dstLangCode));
            exit();
        }

        // check if the source namespace directory exists
        $this->srcNamespaceDir = $this->pagesDir . DS . $this->srcLangCode;
        if (!is_dir($this->srcNamespaceDir)) {

            // if not found, report an error
            self::return_this('Not Found',  sprintf($this->getLang('obsNamespaceNotFound'), $this->srcLangCode));
            exit();
        }

        // check if the desired source directory exists
        $this->srcDir = $this->srcNamespaceDir . DS . $desiredSubDirectory;
        if (!is_dir($this->srcDir)) {

            // if not found, report an error
            self::return_this('Not Found',  sprintf($this->getLang('obsSourceDirNotFound'), $desiredSubDirectory, $this->srcLangCode));
            exit();
        }

        // check if the desired destination directory already exists
        $this->dstDir = $this->dstNamespaceDir . DS . $desiredSubDirectory;
        if (is_dir($this->dstDir)) {

            // if the directory exists, are there txt files in it?
            $files = glob($this->dstDir . DS . '*.txt', GLOB_NOSORT);
            if (!empty($files) && (count($files) > 5)) {

                // if there are, report an error
                if (strpos($desiredSubDirectory, 'questions') !== false)
                    self::return_this('Directory Exists', sprintf($this->getLang('obsQuestionsDestinationDirExists'), "/$this->dstLangCode/obs/notes/questions/home"));
                elseif (strpos($desiredSubDirectory, 'kt') !== false)
                    self::return_this('Directory Exists', sprintf($this->getLang('obsWordsDestinationDirExists'), "/$this->dstLangCode/obe/kt/home"));
                elseif (strpos($desiredSubDirectory, 'notes') !== false)
                    self::return_this('Directory Exists', sprintf($this->getLang('obsNotesDestinationDirExists'), "/$this->dstLangCode/obs/notes/home"));
                else
                    self::return_this('Directory Exists', sprintf($this->getLang('obsDestinationDirExists'), "/$this->dstLangCode/obs"));

                exit();
            }
        }
    }
    
    public function initialize_obs_content() {

        $this->prepare_for_initialization('obs');

        $msg = '';

        // some files will come from the templates directory
        $templateDir = $this->pagesDir . '/templates/obs3/obs';

        // Now copy the obs files from $this->srcDir to $this->dstDir
        $this->copy_obs_files($this->srcDir, $this->dstDir, $templateDir, $this->srcLangCode, $this->dstLangCode);

        // update home.txt
        $templateDir = $this->pagesDir . '/templates';
        $this->update_home_txt($templateDir, $this->dstNamespaceDir, $this->dstLangCode);

        // update sidebar.txt
        $this->update_sidebar_txt($templateDir, $this->dstNamespaceDir, $this->dstLangCode);

        // make uwadmin status page
        $adminDir = $this->pagesDir . "/en/uwadmin";
        $this->copy_status_txt($templateDir, $adminDir, $this->dstLangCode);

        // git add, commit, push
        $msg .= self::git_push($adminDir, 'Added uwadmin obs page for ' . $this->dstLangCode);
        $msg .= self::git_push($this->dstNamespaceDir, 'Initial import of OBS');

        self::return_this('OK', $msg . sprintf($this->getLang('obsCreatedSuccess'), $this->dstLangCode, "/$this->dstLangCode/obs"));
    }

    public function initialize_obs_notes() {

        $this->prepare_for_initialization('obs' . DS . 'notes');

        $msg = '';

        // copy the obs notes files from $this->srcDir to $this->dstDir
        $this->copy_obs_text_files($this->srcDir, $this->dstDir, $this->srcLangCode, $this->dstLangCode);

        // copy notes/frames directory
        $this->copy_obs_text_files($this->srcDir . DS . 'frames', $this->dstDir . DS . 'frames', $this->srcLangCode, $this->dstLangCode);

        // create notes.txt
        $srcFile = dirname($this->srcDir) . DS . 'notes.txt';
        $this->copy_text_file($srcFile, dirname($this->dstDir), $this->srcLangCode, $this->dstLangCode);

        // create home.txt
        $srcFile = $this->srcDir . DS . 'home.txt';
        $this->copy_text_file($srcFile, $this->dstDir, $this->srcLangCode, $this->dstLangCode);

        // copy scripture notes
        $this->srcDir = $this->srcNamespaceDir . DS . 'bible' . DS . 'notes';
        $this->dstDir = $this->dstNamespaceDir . DS . 'bible' . DS . 'notes';
        $this->copy_obs_text_files($this->srcDir, $this->dstDir, $this->srcLangCode, $this->dstLangCode);
        if (is_dir($this->srcDir)) {
            $dirs = glob($this->srcDir . DS . '*', GLOB_ONLYDIR);
            foreach($dirs as $dir) {
                if ($dir != $this->srcDir)
                    $this->copy_obs_text_files($dir, $this->dstDir . DS . basename($dir), $this->srcLangCode, $this->dstLangCode, true);
            }
        }

        // git add, commit, push
        $msg .= self::git_push($this->dstNamespaceDir, 'Initial import of OBS tN');

        self::return_this('OK', $msg . sprintf($this->getLang('obsNotesCreatedSuccess'), $this->dstLangCode, "/$this->dstLangCode/obs/notes/home"));
    }

    public function initialize_obs_words() {

        $this->prepare_for_initialization('obe' . DS . 'kt');

        $msg = '';

        // copy the key terms files from $this->srcDir to $this->dstDir
        $this->copy_obs_text_files($this->srcDir, $this->dstDir, $this->srcLangCode, $this->dstLangCode);

        // copy the 'other' files from $this->srcDir to $this->dstDir
        $srcDir = dirname($this->srcDir) . DS . 'other';
        $dstDir = dirname($this->dstDir) . DS . 'other';
        $this->copy_obs_text_files($srcDir, $dstDir, $this->srcLangCode, $this->dstLangCode);

        // create ktobs.txt
        $srcFile = dirname($this->srcDir) . DS . 'ktobs.txt';
        $this->copy_text_file($srcFile, dirname($this->dstDir), $this->srcLangCode, $this->dstLangCode);

        // create home.txt
        $srcFile = dirname($this->srcDir) . DS . 'home.txt';
        $this->copy_text_file($srcFile, dirname($this->dstDir), $this->srcLangCode, $this->dstLangCode);

        // git add, commit, push
        $msg .= self::git_push($this->dstNamespaceDir, 'Initial import of OBS tW');

        self::return_this('OK', $msg . sprintf($this->getLang('obsWordsCreatedSuccess'), $this->dstLangCode, "/$this->dstLangCode/obe/kt/home"));
    }

    public function initialize_obs_questions() {

        $this->prepare_for_initialization('obs' . DS . 'notes' . DS . 'questions');

        $msg = '';

        // copy the obs questions files from $this->srcDir to $this->dstDir
        $this->copy_obs_text_files($this->srcDir, $this->dstDir, $this->srcLangCode, $this->dstLangCode);

        // git add, commit, push
        $msg .= self::git_push($this->dstNamespaceDir, 'Initial import of OBS tQ');

        self::return_this('OK', $msg . sprintf($this->getLang('obsQuestionsCreatedSuccess'), $this->dstLangCode, "/$this->dstLangCode/obs/notes/questions/home"));
    }

    /**
     * @param string $languageName
     * @return string
     */
    private static function get_lang_code_from_language_name_string($languageName) {

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
    private static function check_namespace($namespaceDir) {
        return is_dir($namespaceDir);
    }

    /**
     * Copies the OBS files to the destination directory using the JSON source
     * @param string $srcDir
     * @param string $dstDir
     * @param string $templateDir
     * @param string $srcLangCode
     * @param string $dstLangCode
     */
    private static function copy_obs_files($srcDir, $dstDir, $templateDir, $srcLangCode, $dstLangCode) {

        if (!is_dir($dstDir))
            mkdir($dstDir, 0755, true);

        // create the 01.txt through 50.txt source files
        self::create_files_from_json($srcLangCode, $dstDir);

        // copy some files from source directory
        $files = array('back-matter.txt', 'front-matter.txt', 'cover-matter.txt');
        foreach($files as $file) {

            $srcFile = $srcDir . DS . $file;
            if (!is_file($srcFile)) continue;

            $outFile = $dstDir . DS . $file;
            copy($srcFile, $outFile);
            chmod($outFile, 0644);
        }

        // create the obs.txt home page
        $srcFile = dirname($srcDir) . DS . 'obs.txt';
        if (!is_file($srcFile))
            $srcFile = dirname($templateDir) . DS . 'obs.txt';

        $outFile = dirname($dstDir) . DS . 'obs.txt';
        self::copy_template_file($srcFile, $outFile, $dstLangCode);

        // copy these files from /templates/obs3/obs
        $files = array('sidebar.txt', 'stories.txt');
        foreach($files as $file) {

            $srcFile = $templateDir . DS . $file;
            $outFile = $dstDir . DS . $file;
            self::copy_template_file($srcFile, $outFile, $dstLangCode);
        }
    }

    /**
     * @param string $srcLangCode
     * @param string $dstDir
     */
    private static function create_files_from_json($srcLangCode, $dstDir) {

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
                $text .= self::add_frame($frame['img'], $frame['text']);
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

    /**
     * Builds the Dokuwiki markdown for a single frame of a story
     * @param $imgUrl
     * @param $text
     * @return string
     */
    private static function add_frame($imgUrl, $text) {

        // the image
        $returnVal = "\n{{" . $imgUrl . "}}\n\n";

        // the text
        $returnVal .= "\n{$text}\n\n";

        // leave room for the translation
        $returnVal .= "\n\n";

        return $returnVal;
    }

    /**
     * Copies an OBS template file in Dokuwiki markdown file, replacing 'LANGCODE' with the actual code
     * @param string $srcFile
     * @param string $outFile
     * @param string $dstLangCode
     */
    private static function copy_template_file($srcFile, $outFile, $dstLangCode) {

        $text = file_get_contents($srcFile);
        file_put_contents($outFile, str_replace('LANGCODE', $dstLangCode, $text));
        chmod($outFile, 0644);
    }

    /**
     * Adds OBS to the namespace home.txt file
     * @param string $templateDir
     * @param string $dstNamespaceDir
     * @param string $dstLangCode
     */
    private static function update_home_txt($templateDir, $dstNamespaceDir, $dstLangCode) {

        $homeFile = $dstNamespaceDir . DS . 'home.txt';
        if (!is_file($homeFile)) {

            $srcFile = $templateDir . DS . 'home.txt';
            self::copy_template_file($srcFile, $homeFile, $dstLangCode);
        }

        $text = file_get_contents($homeFile);
        $text .= "\n===== Resources =====\n\n  * **[[{$dstLangCode}:obs|Open Bible Stories ({$dstLangCode})]]**";
        file_put_contents($homeFile, $text);
    }

    /**
     * Adds OBS to the namespace sidebar.txt file
     * @param string $templateDir
     * @param string $dstNamespaceDir
     * @param string $dstLangCode
     */
    private static function update_sidebar_txt($templateDir, $dstNamespaceDir, $dstLangCode) {

        $sidebarFile = $dstNamespaceDir . DS . 'sidebar.txt';
        if (!is_file($sidebarFile)) {

            $srcFile = $templateDir . DS . 'sidebar.txt';
            self::copy_template_file($srcFile, $sidebarFile, $dstLangCode);
        }

        $text = file_get_contents($sidebarFile);
        $text .= "\n**Resources**\n\n  * [[{$dstLangCode}:obs|Open Bible Stories ({$dstLangCode})]]\n\n**Latest OBS Status**\n{{page>en:uwadmin:{$dstLangCode}:obs:status}}";
        file_put_contents($sidebarFile, $text);
    }

    /**
     * @param string $templateDir
     * @param string $adminDir
     * @param string $dstLangCode
     */
    private static function copy_status_txt($templateDir, $adminDir, $dstLangCode) {

        $adminDir .= "/{$dstLangCode}/obs";
        if (!is_dir($adminDir)) mkdir($adminDir, 0755, true);

        $statusFile = $adminDir . DS . 'status.txt';
        $srcFile = $templateDir . DS . 'status.txt';

        $text = file_get_contents($srcFile);
        $text = str_replace('ORIGDATE', date('Y-m-d'), $text);

        file_put_contents($statusFile, $text);
    }

    /**
     * @param string $dir
     * @param string $msg
     * @return string
     */
    private static function git_push($dir, $msg) {

        // skip this section during unit testing
        if(!defined('DOKU_UNITTEST')) return 'Skipping git_push during testing<br>';

        // initialize the return value
        $returnVal = '';

        // update changes pages
        $script = '/var/www/vhosts/door43.org/tools/obs/dokuwiki/obs-gen-changes-pages.sh';
        if (is_file($script))
            shell_exec($script);

        $originalDir = getcwd();

        chdir($dir);

        // the 2>&1 redirect sends errorOut to stdOut
        $result1 = shell_exec('git add . 2>&1');
        $result2 = shell_exec('git commit -am "' . $msg . '" 2>&1');
        $result3 = shell_exec('git push origin master 2>&1');

        // show the git output in a development environment
        if (($_SERVER['SERVER_NAME'] == 'localhost') || ($_SERVER['SERVER_NAME'] == 'test.door43.org'))
            $returnVal = "<br>Git Response: $result1<br><br>Git Response: $result2<br><br>Git Response: $result3<br><br>";

        chdir($originalDir);

        return $returnVal;
    }

    /**
     * @param string $srcDir
     * @param string $dstDir
     * @param string $srcLangCode
     * @param string $dstLangCode
     * @param bool $recursive
     */
    private static function copy_obs_text_files($srcDir, $dstDir, $srcLangCode, $dstLangCode, $recursive = false) {

        if (!is_dir($dstDir))
            mkdir($dstDir, 0755, true);

        $files = glob($srcDir . DS . '*.txt');
        foreach($files as $file) {
            self::copy_text_file($file, $dstDir, $srcLangCode, $dstLangCode);
        }

        // if recursive, step through the sub-directories also
        if ($recursive) {

            $dirs = glob($srcDir . DS . '*', GLOB_ONLYDIR);

            foreach($dirs as $dir) {
                if ($dir != $srcDir)
                    self::copy_obs_text_files($dir, $dstDir . DS . basename($dir), $srcLangCode, $dstLangCode, true);
            }
        }
    }

    /**
     * @param string $srcFile
     * @param string $dstDir
     * @param string $srcLangCode
     * @param string $dstLangCode
     */
    private static function copy_text_file($srcFile, $dstDir, $srcLangCode, $dstLangCode) {

        // do not overwrite existing files
        $dstFile = $dstDir . DS . basename($srcFile);
        if (file_exists($dstFile)) return;

        // read the source file
        $srcText = file_get_contents($srcFile);

        // replace source language code
        $dstText = str_replace("[:{$srcLangCode}:", "[:{$dstLangCode}:", $srcText);

        file_put_contents($dstFile, $dstText);
        chmod($dstFile, 0644);
    }

    /**
     * Send the results back to the browser as a json object.
     * @param string $result
     * @param string $msg
     */
    private static function return_this($result, $msg) {

        header('Content-Type: application/json');
        echo json_encode(new ObsAjaxResults($result, $msg));
    }
}


