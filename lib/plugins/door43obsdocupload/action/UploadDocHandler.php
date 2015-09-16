<?php
/**
 * Name: UploadDocHandler.php
 * Description: A Dokuwiki action plugin to process uploaded DOCX files.
 *
 * Author: Phil Hopper
 * Date:   2015-09-14
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
 * Class action_plugin_door43obsdocupload_UploadDocHandler
 */
class action_plugin_door43obsdocupload_UploadDocHandler extends Door43_Action_Plugin {

    /**
     * @var $door43shared helper_plugin_door43shared
     */
    private $shared_helper;

    function __construct() {
        parent::__construct();

        $this->shared_helper = plugin_load('helper', 'door43shared');
    }

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller the DokuWiki event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {
        Door43_Ajax_Helper::register_handler($controller, 'upload_obs_docx', array($this, 'upload_obs_docx'));
        Door43_Ajax_Helper::register_handler($controller, 'publish_obs_docx', array($this, 'publish_obs_docx'));
    }

    /**
     * This is called when a file is uploaded
     */
    public function upload_obs_docx() {

        global $INPUT;
        global $conf;

        $result = 'Failed';
        $msg = '';

        try {

            $lang = $INPUT->str('lang');
            $langCode = '[Missing]';

            if (preg_match('/^.*\((.+)\)$/', $lang, $matches) === 1) {
                $langCode = $matches[1];
            }

            // check the target namespace and obs directory
            $obsDir = $conf['datadir'] . DS . $langCode . DS . 'obs';
            if (!is_dir($obsDir)) {

                // obs must be initialized for this language
                self::return_this($result, sprintf($this->getLang('obsNotInitialized'), $langCode, DOKU_BASE . 'obs-setup'));
            }

            $file = $_FILES['file'];

            // the file is uploaded to the temp directory as $file['tmp_name']
            if (($file['error'] == UPLOAD_ERR_OK) && is_file($file['tmp_name'])) {

                $temp_dir = sys_get_temp_dir() . '/obs_docx-' . microtime(true);
                mkdir($temp_dir);

                // get the file extension
                $ext = utf8_strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if ($ext == 'docx') {

                    // move the docx file to our temp directory
                    $docx_file = $temp_dir . DS . $file['name'];
                    move_uploaded_file($file['tmp_name'], $docx_file);
                }
                elseif ($ext == 'zip') {

                    // unzip the archive into our temp directory
                    $zip = new ZipArchive();
                    if ($zip->open($file['tmp_name']) === true) {
                        $zip->extractTo($temp_dir);
                        $zip->close();
                    }

                    // there should be just one docx file
                    $dir_iterator = new RecursiveDirectoryIterator($temp_dir);
                    $file_iterator = new RecursiveIteratorIterator($dir_iterator);
                    $files = iterator_to_array(new RegexIterator($file_iterator, '/^.+\.docx$/i', RecursiveRegexIterator::MATCH));
                    $file_count = count($files);

                    if ($file_count == 0) {

                        // no docx files were found in the zip archive
                        self::return_this($result, $this->getLang('zipNoDocxFiles'));
                    }
                    elseif ($file_count > 1) {

                        // more than one docx file was found in the zip archive
                        self::return_this($result, $this->getLang('zipMultipleDocxFiles'));
                    }

                    // just one docx file was found in the zip archive
                    $docx_file = $files[0];
                }
                else {

                    // extension of the uploaded file is not .docx or .zip
                    self::return_this($result, $this->getLang('fileTypeNotSupported'));
                }

                // if no docx file could be found, we assume the wrong file type was uploaded
                /** @var string $docx_file */
                if (!is_file($docx_file)) {
                    self::return_this($result, $this->getLang('fileTypeNotSupported'));
                }

                // we have a docx file, now process it
                self::process_docx_file($docx_file, $langCode, $result, $msg);

                // clean-up temp files
                $this->shared_helper->delete_directory_and_files($temp_dir);
            }
            else {
                $msg = $this->getLang('fileNotUploaded');
            }
        }
        catch(Exception $e) {
            $msg = $e->getMessage();
        }

        // return the results
        self::return_this($result, $msg);
    }

    /**
     * Send the results back to the browser as a json object and exit.
     * @param string $result
     * @param string $msg
     */
    private static function return_this($result, $msg) {

        header('Content-Type: application/json');
        echo json_encode(array('result' => $result, 'htmlMessage' => $msg));
        exit();
    }

    private function process_docx_file($fileName, $langCode, &$result, &$msg) {

        global $conf;

        // get our working temp directory
        $cwd = dirname($fileName);

        // convert to dokuwiki format with pandoc
        $dwFile = $cwd . DS . 'obs.dw';
        $cmd = "/usr/bin/pandoc -s \"$fileName\" -t dokuwiki -o \"$dwFile\"";
        exec($cmd, $output, $error);

        // check the return code, a non-zero code indicates an error
        if ($error !== 0) {

            // format the output
            $msg = $this->getLang('pandocError');

            if (count($output)) {

                $msg .= "<br>\n<pre><code>\n";

                foreach($output as $output_line) {
                    $msg .= $output_line . "\n";
                }
                $msg .= "</code></pre>";
            }

            return;
        }

        // the file was converted successfully
        $text = file_get_contents($dwFile);

        // split into stories
        $stories = array_filter(explode('====== ', $text));

        // discard the license page
        while (count($stories) > 50) {
            array_shift($stories);
        }

        // the target namespace and obs directory
        $obsDir = $conf['datadir'] . DS . $langCode . DS . 'obs';

        // create an empty preview import directory
        $obsPreviewDir = $conf['datadir'] . DS . $langCode . DS . 'obs-preview';
        $this->shared_helper->delete_directory_and_files($obsPreviewDir);
        if (!is_dir($obsPreviewDir)) {
            mkdir($obsPreviewDir, 0755, true);
        }

        // process each story into the preview directory
        for ($story_index = 0; $story_index < count($stories); $story_index++) {

            $story_num = str_pad($story_index + 1, 2, '0', STR_PAD_LEFT);

            // re-add the string we used as a delimiter for splitting
            $story = '====== ' . $stories[$story_index];

            // replace \\ at the end of a line with a line break
            $story = str_replace("\\\\\n", "\n\n", $story);

            // split into lines
            $lines = array_values(array_filter(explode("\n", $story)));

            // process the lines
            $image_index = 1;

            // first line is the title
            $story = $lines[0];

            for ($line_index = 1; $line_index < count($lines); $line_index++) {

                $line = $lines[$line_index];

                // is this the last line?
                if ($line_index == (count($lines) - 1)) {
                    if (preg_match('/^(\/+)([^\/]*)(\/+)$/', $line, $matches) === 1) {
                        $line = $matches[2];
                    }

                    // the last line is italic
                    $line = '//' . $line . '//';
                }

                // is this an image line?
                if ((preg_match('/^(http)(.*)(jpg)(\?.+)*$/', $line, $matches) === 1)
                    || ((preg_match('/^({{)(.*)(}})$/', $line, $matches) === 1))) {

                    $line = '{{https://api.unfoldingword.org/obs/jpg/1/en/360px/obs-en-' . $story_num . '-'
                        . str_pad($image_index, 2, '0', STR_PAD_LEFT) . '.jpg}}';
                    $image_index++;
                }
                $story .= "\n\n" . trim($line);
            }

            // add a final new line
            $story .= "\n";

            // save the story
            file_put_contents($obsPreviewDir . DS . $story_num . '.txt', $story);
        }

        // create the index file
        $index_contents = file_get_contents($obsDir . DS . 'stories.txt');
        $index_contents = str_replace(':obs:', ':obs-preview:', $index_contents);
        file_put_contents($obsPreviewDir . '.txt', $index_contents);

        // we have successfully completed our mission
        $msg = sprintf($this->getLang('docxImportSucceeded'), DOKU_BASE . "{$langCode}/obs-preview");
        $result = 'OK';
    }

    /**
     * After the user has verified the uploaded DOCX file was properly processed, publish it
     */
    public function publish_obs_docx() {

        global $INPUT;
        global $conf;

        $result = 'Failed';

        try {

            $lang = $INPUT->str('lang');
            $langCode = '[Missing]';

            if (preg_match('/^.*\((.+)\)$/', $lang, $matches) === 1) {
                $langCode = $matches[1];
            }

            // the target namespace and obs directory
            $obsDir = $conf['datadir'] . DS . $langCode . DS . 'obs';

            // the preview obs directory
            $obsPreviewDir = $conf['datadir'] . DS . $langCode . DS . 'obs-preview';

            // both must exist
            if (!is_dir($obsDir)) {

                // obs must be initialized for this language
                self::return_this($result, sprintf($this->getLang('obsNotInitialized'), $langCode, DOKU_BASE . 'obs-setup'));
            }

            if (!is_dir($obsPreviewDir)) {

                // the obs preview directory needs to have been created
                self::return_this($result, $this->getLang('previewNotFound'));
            }

            // copy 01.txt - 50.txt from preview to the obs directory
            for ($i = 1; $i < 51; $i++) {

                $storyFile = str_pad($i, 2, '0', STR_PAD_LEFT) . '.txt';
                $srcFile = $obsPreviewDir . DS . $storyFile;
                $dstFile = $obsDir . DS . $storyFile;

                // make sure the source file exists
                if (!is_file($srcFile)) {
                    self::return_this($result, sprintf($this->getLang('sourceFileNotFound'), $srcFile));
                }

                if (!copy($srcFile, $dstFile)) {
                    self::return_this($result, sprintf($this->getLang('notAbleToCopy'), $storyFile));
                }
            }

            // delete the preview directory
            $this->shared_helper->delete_directory_and_files($obsPreviewDir);

            // success message
            $result = 'OK';
            $msg = $this->getLang('publishSucceeded');
        }
        catch(Exception $e) {
            $msg = $e->getMessage();
        }

        // return the results
        self::return_this($result, $msg);
    }
}
