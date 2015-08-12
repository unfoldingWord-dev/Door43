<?php
/**
 * PHP version 5
 *
 * Activity by user report
 *
 * @category Door43
 * @package  Chunkprogress
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */

/**
 * General tests
 *
 * @category Door43
 * @package  Chunkprogress
 * @author   Craig Oliver <craig_oliver@wycliffeassociates.org>
 * @license  GPL2 (I think)
 * @link     ???
 */
class general_plugin_door43obs_test extends DokuWikiTest
{

    /**
     * Simple test to make sure the plugin.info.txt is in the correct format
     * @return nothing
     */
    public function test_plugininfo()
    {
        $file = __DIR__.'/../plugin.info.txt';
        $this->assertFileExists($file);

        $info = confToHash($file);

        $this->assertArrayHasKey('base', $info);
        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('email', $info);
        $this->assertArrayHasKey('date', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('desc', $info);
        $this->assertArrayHasKey('url', $info);

        $this->assertEquals('chunkprogress', $info['base']);
        $this->assertRegExp('/^https?:\/\//', $info['url']);
        $this->assertTrue(mail_isvalid($info['email']));
        $this->assertRegExp('/^\d\d\d\d-\d\d-\d\d$/', $info['date']);
        $this->assertTrue(false !== strtotime($info['date']));
    }

    /**
     * Run activity by namespace report, verify correct values appear 
     * @return nothing
     */
    public function test_activity_by_namespace()
    {
        // copy the test data
        echo TMP_DIR;
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/attic/');

        $this->pluginsEnabled[] = 'chunkprogress';
        $thisPlugin = plugin_load('syntax', 'chunkprogress_chunkprogress');
        $request = new TestRequest();
        $response = $request->get(
            array('id' => 'test_activity_by_namespace'), '/doku.php'
        );
        $content = $response->getContent();

        echo $content;

    }
}
