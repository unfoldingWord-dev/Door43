<?php
/**
 * Name: cache.php
 * Description: Simple caching for door43 plugins to use
 *
 * Author: Phil Hopper
 * Date:   2015-04-09
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class door43Cache extends DokuWiki_Plugin {

    /**
     * @var door43Cache
     */
    private static $instance;
    private static $cacheDir;
    private static $cacheHours = 1;

    /**
     * Private because this class should be a singleton
     * @throws Exception
     */
    private function __construct() {

        global $conf;

        // make sure the cache directory exists
        $cacheDir = $conf['cachedir'] . DIRECTORY_SEPARATOR . 'door43_cache';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

        // if not able to create the cache directory, throw an exception
        if (!is_dir($cacheDir)) {
            throw new Exception('Directory not found: ' . $cacheDir);
        }

        self::$cacheDir = $cacheDir;
    }

    /**
     * Gets the singleton
     * @return door43Cache
     */
    static function getInstance() {

        if (empty(self::$instance)) {
            self::$instance = new door43Cache();
        }

        return self::$instance;
    }

    /**
     * @param string $fileName Can include a subdirectory also
     * @param string $data
     */
    function saveString($fileName, $data) {

        $cacheFile = self::$cacheDir . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($cacheFile, $data);
    }

    /**
     * @param string $fileName The same string used to save
     * @return mixed Returns a string if the file exists, null if the is file not found
     */
    function getString($fileName) {

        $cacheFile = self::$cacheDir . DIRECTORY_SEPARATOR . $fileName;

        // does the file exist?
        if (is_file($cacheFile)) {

            // if it exists, is it expired?
            if (filectime($cacheFile) < strtotime('-' . self::$cacheHours . ' hour')) {

                // delete the file if it is over self::$cacheHours old
                unlink($cacheFile);
            }
            else {

                // file exists and is not expired, so return the contents
                return file_get_contents($cacheFile);
            }
        }

        // if you are here, the file does not exist, or the cache expired
        return null;
    }

    /**
     * @param string $fileName Can include a subdirectory also
     * @param mixed  $object
     * @throws Exception
     */
    function saveObject($fileName, $object) {

        $data = json_encode($object);

        if ($data === false) {
            throw new Exception('Failed to successfully encode the object for caching.');
        }

        self::saveString($fileName, $data);
    }

    /**
     * @param string $fileName The same string used to save
     * @param bool   $assoc
     * @return mixed Returns a object if the file exists, null if the is file not found
     */
    function getObject($fileName, $assoc = false) {

        $raw = self::getString($fileName);

        if (empty($raw)) {
            return null;
        }

        return json_decode($raw, $assoc);
    }
}
