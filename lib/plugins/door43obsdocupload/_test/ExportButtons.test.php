<?php
/**
 * Name: ExportButtons.test.php
 * Description: Tests for the CreateNow syntax plugin.
 *
 * Author: Phil Hopper
 * Date:   2015-08-12
 */

class ExportButtons_plugin_test extends DokuWikiTest {

    static $outputBuffer;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // copy the test data
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');
    }

    public function setUp() {
        self::$outputBuffer = '';
        $this->pluginsEnabled[] = 'include';
        $this->pluginsEnabled[] = 'door43translation';
        $this->pluginsEnabled[] = 'door43shared';
        $this->pluginsEnabled[] = 'door43obsdocupload';
        parent::setUp();
    }

    /**
     * Verify the javascript was correctly rendered.
     */
    public function test_render_script() {

        $thisPlugin = plugin_load('action', 'door43obsdocupload_ExportButtons');
        $request = new TestRequest();
        $response = $request->get(array('id' => 'en:obs'), '/doku.php');
        $content = $response->getContent();

        // output check
        $this->assertNotEmpty($content);
        $this->assertContains('function exportSourceTemplate()', $content);
        $this->assertContains($thisPlugin->lang['exportTitle'], $content);
    }

    /**
     * Verify the toolstrip button was correctly rendered.
     */
    public function test_render_toolstrip_button() {

        $thisPlugin = plugin_load('action', 'door43obsdocupload_ExportButtons');
        $request = new TestRequest();
        $response = $request->get(array('id' => 'en:obs'), '/doku.php');
        $content = $response->getContent();

        // output check
        $this->assertNotEmpty($content);
        $this->assertContains($thisPlugin->lang['getTemplate'], $content);
    }

    /**
     * Attempt to verify the dialog is properly loaded.
     *
     * This is a hack since there is no way yet to test an ajax request in Dokuwiki.
     */
    public function test_render_dialog() {

        /* @var $thisPlugin action_plugin_door43obsdocupload_ExportButtons */
        $thisPlugin = plugin_load('action', 'door43obsdocupload_ExportButtons');

        if (ob_get_length()) ob_clean();
        ob_start('ExportButtons_plugin_test::catch_output');

        $thisPlugin->get_obs_doc_export_dlg();

        ob_end_flush();

        // output check
        $this->assertNotEmpty(self::$outputBuffer);
        $this->assertContains($thisPlugin->lang['draftLabel'], self::$outputBuffer);
    }

    /**
     * This is not currently testable because it requires pandoc
     */
    public function test_download_obs_template() {
        $this->markTestSkipped('Requires pandoc.');
    }

    /**
     * This is the output callback function to avoid writing to stdout.
     * @param $buffer
     */
    public static function catch_output($buffer) {
        self::$outputBuffer .= $buffer;
    }
}
