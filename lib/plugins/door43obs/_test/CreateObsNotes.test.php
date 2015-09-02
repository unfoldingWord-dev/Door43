<?php
/**
 * Name: CreateObsNotes.test.php
 * Description: Tests for the CreateObsNotes syntax plugin.
 *
 * Author: Phil Hopper
 * Date:   2015-08-04
 */

class CreateObsNotes_plugin_test extends DokuWikiTest {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // copy the test data
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');
    }

    public function setUp() {
        $this->pluginsEnabled[] = 'include';
        $this->pluginsEnabled[] = 'door43translation';
        $this->pluginsEnabled[] = 'door43shared';
        $this->pluginsEnabled[] = 'door43obs';
        parent::setUp();
    }

    /**
     * Verify the button was correctly rendered.
     */
    public function test_render_button () {

        // TODO: fix this test
        $this->markTestSkipped('The test needs fixed');

        $thisPlugin = plugin_load('syntax', 'door43obs_CreateObsNotes');
        $request = new TestRequest();
        $response = $request->get(array('id' => 'obs-setup'), '/doku.php');
        $content = $response->getContent();

        // output check
        $this->assertNotEmpty($content);
        $this->assertContains($thisPlugin->lang['createObsNotesButtonText'], $content);
    }
}
