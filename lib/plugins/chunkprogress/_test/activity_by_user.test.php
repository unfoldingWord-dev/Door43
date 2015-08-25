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
class user_plugin_chunkprogress_test extends DokuWikiTest
{

    /**
     * Set up before class
     * @return nothing
     */
    public static function setUpBeforeClass() 
    {
        parent::setUpBeforeClass();

        // copy the test data
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');
    }

    /**
     * Set up before test
     * @return nothing
     */
    public function setUp() 
    {
        // Enable our plugin
        $this->pluginsEnabled[] = 'chunkprogress';

        // Restore our global variable because PHPUnit nukes it
        // NOTE: If this changes in utils.php it needs to change here too!
        global $CHUNKPROGRESS_STATUS_TAGS;
        $CHUNKPROGRESS_STATUS_TAGS = array(
            "draft", "check", "review", "text", "discuss", "publish");

        // Continue with regular setup
        parent::setUp();
    }

    /**
     * Regression test: run activity by user report, verify correct values 
     * appear 
     *
     * @return nothing
     */
    public function test_activity_by_user()
    {
        $request = new TestRequest();
        $response = $request->get(
            array('id' => 'test_activity_by_user'), '/doku.php'
        );
        $content = $response->getContent();

        $this->assertNotEmpty($content);

        // Verify totals
        $this->assertRegExp(
            "/cnewton<[^>]*><[^>]*><[^>]*><[^>]*><[^>]*><[^>]*>16/", $content
        );
        $this->assertRegExp(
            "/theologyjohn<[^>]*><[^>]*><[^>]*><[^>]*><[^>]*><[^>]*>16/", $content
        );
        $this->assertRegExp(
            "/TOTAL<[^>]*><[^>]*><[^>]*><[^>]*><[^>]*><[^>]*>32/", $content
        );

        // need to do this because strtotime uses the timezone on the server
        $start_timestamp = strtotime('2015-07-01');
        $end_timestamp = strtotime('2015-07-04');

        // Verify timestamps were understood correctly
        $this->assertRegExp(
            "/start_timestamp<[^>]*><[^>]*>{$start_timestamp}/", $content
        );
        $this->assertRegExp(
            "/end_timestamp<[^>]*><[^>]*>{$end_timestamp}/", $content
        );

        // Verify page counts 
        $this->assertRegExp(
            "/debug_num_pages_in_ns<[^>]*><[^>]*>70/", $content
        );
        $this->assertRegExp(
            "/debug_num_revisions_in_ns<[^>]*><[^>]*>2039/", $content
        );
        $this->assertRegExp(
            "/debug_num_revisions_within_dates<[^>]*><[^>]*>169/", $content
        );

    }
}
