<?php
/**
 * Name: PopulateOBS.test.php
 * Description: Tests for the PopulateOBS action plugin.
 *
 * Author: Phil Hopper
 * Date:   2015-08-04
 */

class PopulateOBS_plugin_test extends DokuWikiTest {

    private $obsSrcDir;
    private $destNsDir;
    private $templateDir;

    private static $srcNs = 'en';
    private static $destNs = 'x-obs-unit-test';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
    }

    public function setUp() {
        $this->pluginsEnabled[] = 'include';
        $this->pluginsEnabled[] = 'translation';
        $this->pluginsEnabled[] = 'door43shared';
        $this->pluginsEnabled[] = 'door43obs';

        /** @var $INPUT input */
        global $INPUT;
        $INPUT->get->set('sourceLang', self::$srcNs);
        $INPUT->get->set('destinationLang', self::$destNs);

        // create source files in ./pages/en/obs
        $this->obsSrcDir = TMP_DIR . '/data/pages/' . self::$srcNs . '/obs';
        if (!is_dir($this->obsSrcDir)) mkdir($this->obsSrcDir, 0755, true);

        file_put_contents($this->obsSrcDir . '/back-matter.txt', 'back-matter.txt');
        file_put_contents($this->obsSrcDir . '/front-matter.txt', 'front-matter.txt');
        file_put_contents($this->obsSrcDir . '/cover-matter.txt', 'cover-matter.txt');

        // create source files in ./pages/templates/obs3/obs
        $this->templateDir = TMP_DIR . '/data/pages/templates';
        if (!is_dir($this->templateDir)) mkdir($this->templateDir, 0755, true);

        $obs3Dir = $this->templateDir . '/obs3/obs';
        if (!is_dir($obs3Dir)) mkdir($obs3Dir, 0755, true);
        file_put_contents($obs3Dir . '/sidebar.txt', 'sidebar.txt');
        file_put_contents($obs3Dir . '/stories.txt', 'stories.txt');

        // create source files in ./pages/templates (home.txt, sidebar.txt, status.txt)
        file_put_contents($this->templateDir . '/home.txt', 'home.txt');
        file_put_contents($this->templateDir . '/sidebar.txt', 'sidebar.txt');
        file_put_contents($this->templateDir . '/status.txt', 'status.txt');
        file_put_contents($this->templateDir . '/obs3/obs.txt', 'obs.txt');

        // create target namespace in ./pages/x-unit-test
        $this->destNsDir = TMP_DIR . '/data/pages/' . self::$destNs;
        if (!is_dir($this->destNsDir)) mkdir($this->destNsDir, 0755, true);

        parent::setUp();
    }

    public function test_initialize_obs_content() {

        /** @var $thisPlugin action_plugin_door43obs_PopulateOBS */
        $thisPlugin = plugin_load('action', 'door43obs_PopulateOBS');

        if (ob_get_contents()) ob_clean();

        $thisPlugin->initialize_obs_content();

        $result = ob_get_clean();

        // test the return value
        $expect = sprintf($thisPlugin->get_success_message('obsCreatedSuccess'), self::$destNs, '/' . self::$destNs . '/obs');
        $this->assertEquals($expect, $result);

        // check for files
        $this->assertFileExists($this->destNsDir . '/home.txt');
        $this->assertFileExists($this->destNsDir . '/obs.txt');
        $this->assertFileExists($this->destNsDir . '/sidebar.txt');
        $this->assertFileExists($this->destNsDir . '/obs/app_words.txt');
        $this->assertFileExists($this->destNsDir . '/obs/back-matter.txt');
        $this->assertFileExists($this->destNsDir . '/obs/front-matter.txt');
        $this->assertFileExists($this->destNsDir . '/obs/cover-matter.txt');
        $this->assertFileExists($this->destNsDir . '/obs/sidebar.txt');
        $this->assertFileExists($this->destNsDir . '/obs/stories.txt');

        // currently not checking all 50 files, just the first and last
        $this->assertFileExists($this->destNsDir . '/obs/01.txt');
        $this->assertFileExists($this->destNsDir . '/obs/50.txt');
    }
}
