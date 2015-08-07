<?php
/**
 * Name: CreateNow.test.php
 * Description: Tests for the CreateNow syntax plugin.
 *
 * Author: Phil Hopper
 * Date:   2015-08-04
 */

class CreateNow_plugin_test extends DokuWikiTest {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // copy the test data
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');
    }

    public function setUp() {
        $this->pluginsEnabled[] = 'include';
        $this->pluginsEnabled[] = 'translation';
        $this->pluginsEnabled[] = 'door43shared';
        $this->pluginsEnabled[] = 'door43obs';
        parent::setUp();
    }

    /**
     * Verify the button was correctly rendered.
     */
    public function test_render_button () {

        $thisPlugin = plugin_load('syntax', 'door43obs_CreateNow');
        $request = new TestRequest();
        $response = $request->get(array('id' => 'obs-setup'), '/doku.php');
        $content = $response->getContent();

        // output check
        $this->assertNotEmpty($content);
        $this->assertContains($thisPlugin->lang['createButtonText'], $content);
    }
}