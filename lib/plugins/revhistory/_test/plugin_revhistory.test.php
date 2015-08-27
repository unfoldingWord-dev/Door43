<?php
/**
 * Require the DokuWiki_Action_Plugin
 */
require_once(DOKU_INC . 'lib/plugins/revhistory/action.php');

/**
 * Tests to ensure the plugin works as advertised
 * DokuWiki Plugin revhistory
 *
 * @license     Copyright (c) 2015 unfoldingWord http://creativecommons.org/licenses/MIT/
 * @author      Johnathan Pulos <johnathan@missionaldigerati.org>
 *
 * @group plugin_revhistory
 * @group door43_plugins
 * @group plugins
 */
class plugin_revhistory_test extends DokuWikiTest
{
    /**
     * Our plugin to test
     *
     * @var object
     * @access protected
     **/
    protected $plugin;

    /**
     * A mock of the Doku_Event Object
     *
     * @var object
     * @access protected
     **/
    protected $eventHandler;

    /**
     * Setup the testing environment
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function setUp()
    {
        parent::setUp();
        $this->plugin = new action_plugin_revhistory();
        $this->plugin->testing = true;
        $this->eventHandler = $this->getMockBuilder('Doku_Event')
            ->setConstructorArgs(array('ACTION_ACT_PREPROCESS', 'revhistory'))
            ->getMock();
        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');
    }

    /**
     * handleAction() should return JSON
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionReturnsJson()
    {
        $this->plugin->handleAction($this->eventHandler, array());
        $this->assertJson($this->plugin->response);
    }

    /**
     * handleAction() should return all data if no $_GET params are sent except media = 0
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldReturnAllFileDataIfNoOtherParamsSent()
    {
        $expectedCount = 19;
        $_GET['media'] = '0';
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals($expectedCount, count($response['changes']));
    }

    /**
     * handleAction() should return all data & media data if no $_GET params are sent except media = 1
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldReturnAllFileDataAndMediaDataIfNoOtherParamsSent()
    {
        $expectedCount = 21;
        $_GET['media'] = '1';
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals($expectedCount, count($response['changes']));
    }

    /**
     * handleAction() should return changes in ascending order of date
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleMediaShouldReturnAllChangesInDateAscendingOrder()
    {
        $expectedOrder = array(1439988798, 1440525038, 1440702526, 1440702635);
        $_GET['ns'] = 'en:obs';
        $_GET['media'] = '1';
        $_GET['order'] = 'asc';
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $dates = array();
        foreach ($response['changes'] as $change) {
            array_push($dates, $change['date']);
        }
        $this->assertEquals($expectedOrder, $dates);
    }

    /**
     * handleAction() should return changes in descending order of date
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleMediaShouldReturnAllChangesInDateDescendingOrder()
    {
        $expectedOrder = array(1440702635, 1440702526, 1440525038, 1439988798);
        $_GET['ns'] = 'en:obs';
        $_GET['media'] = '1';
        $_GET['order'] = 'desc';
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $dates = array();
        foreach ($response['changes'] as $change) {
            array_push($dates, $change['date']);
        }
        $this->assertEquals($expectedOrder, $dates);
    }

    /**
     * handleAction() should by default return changes in descending order of date
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleMediaShouldReturnAllChangesInDateDescendingOrderByDefault()
    {
        $expectedOrder = array(1440702635, 1440702526, 1440525038, 1439988798);
        $_GET['ns'] = 'en:obs';
        $_GET['media'] = '1';
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $dates = array();
        foreach ($response['changes'] as $change) {
            array_push($dates, $change['date']);
        }
        $this->assertEquals($expectedOrder, $dates);
    }

    /**
     * handleAction() should allow including media files
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldAllowIncludingMediaFiles()
    {
        $_GET['ns'] = 'en:obs:photo.jpg';
        $_GET['media'] = '1';
        $expected = array(
            array(
                'date'      =>  1440702635,
                'ip'        =>  '127.0.0.1',
                'type'      =>  'D',
                'id'        =>  'en:obs:photo.jpg',
                'user'      =>  'admin',
                'sum'       =>  'removed',
                'extra'     =>  '',
                'file_type' =>  'media'
            ),
            array(
                'date'      =>  1440702526,
                'ip'        =>  '127.0.0.1',
                'type'      =>  'C',
                'id'        =>  'en:obs:photo.jpg',
                'user'      =>  'admin',
                'sum'       =>  'created',
                'extra'     =>  '',
                'file_type' =>  'media'
            )
        );
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals('success', $response['success']);
        $this->assertEquals('', $response['error_message']);
        $this->assertEquals($expected, $response['changes']);
    }

    /**
     * handleAction() should return the correct data for a given namespace
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldReturnDataForAGivenNamespace()
    {
        $_GET['ns'] = 'en:obs';
        $_GET['media'] = '0';
        $expected = array(
            array(
                'date'      =>  1440525038,
                'ip'        =>  '65.129.89.43',
                'type'      =>  'E',
                'id'        =>  'en:obs:38',
                'user'      =>  'superdav42',
                'sum'       =>  '',
                'extra'     =>  '',
                'file_type' =>  'file'
            ),
            array(
                'date'      =>  1439988798,
                'ip'        =>  '168.103.130.129',
                'type'      =>  'E',
                'id'        =>  'en:obs',
                'user'      =>  'richmahn',
                'sum'       =>  '[Stories in English]',
                'extra'     =>  '',
                'file_type' =>  'file'
            )
        );
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals('success', $response['success']);
        $this->assertEquals('', $response['error_message']);
        $this->assertEquals($expected, $response['changes']);
    }

    /**
     * handleAction() should return all changes after a specific start date
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldReturnAllChangesAfterASpecificStartDate()
    {
        $_GET['media'] = '0';
        $_GET['start'] = '1439990600';
        $expected = array(
            array(
                'date'      =>  1440525038,
                'ip'        =>  '65.129.89.43',
                'type'      =>  'E',
                'id'        =>  'en:obs:38',
                'user'      =>  'superdav42',
                'sum'       =>  '',
                'extra'     =>  '',
                'file_type' =>  'file'
            ),
            array(
                'date'      =>  1439990719,
                'ip'        =>  '98.221.66.177',
                'type'      =>  'E',
                'id'        =>  'en:bible:notes:1ch:01:01',
                'user'      =>  'craig_oliver',
                'sum'       =>  '[Links:]',
                'extra'     =>  '',
                'file_type' =>  'file'
            ),
            array(
                'date'      =>  1439990614,
                'ip'        =>  '98.221.66.177',
                'type'      =>  'E',
                'id'        =>  'en:bible:notes:1ch:01:08',
                'user'      =>  'craig_oliver',
                'sum'       =>  '[Links:]',
                'extra'     =>  '',
                'file_type' =>  'file'
            )
        );
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals('success', $response['success']);
        $this->assertEquals('', $response['error_message']);
        $this->assertEquals(count($expected), count($response['changes']));
        $this->assertEquals($expected, $response['changes']);
    }

    /**
     * handleAction() should return changes before a specified end date
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldReturnAllChangesBeforeASetEndDate()
    {
        $_GET['media'] = '0';
        $_GET['end'] = '1439000000';
        $expected = array(
            array(
                'date'      =>  1438288255,
                'ip'        =>  '127.0.0.1',
                'type'      =>  'E',
                'id'        =>  'en:bible:notes:1ch:02:01',
                'user'      =>  '',
                'sum'       =>  'external edit',
                'extra'     =>  '',
                'file_type' =>  'file'
            ),
            array(
                'date'      =>  1438288254,
                'ip'        =>  '127.0.0.1',
                'type'      =>  'E',
                'id'        =>  'en:bible:notes:1ch:01:01',
                'user'      =>  '',
                'sum'       =>  'external edit',
                'extra'     =>  '',
                'file_type' =>  'file'
            )
        );
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals('success', $response['success']);
        $this->assertEquals('', $response['error_message']);
        $this->assertEquals(count($expected), count($response['changes']));
        $this->assertEquals($expected, $response['changes']);
    }

    /**
     * handleAction() should return changes in a date range start - end
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldReturnAllChangesBetweenADateRange()
    {
        $_GET['media'] = '0';
        $_GET['start'] = '1439576360';
        $_GET['end'] = '1439906740';
        $expected = array(
            array(
                'date'      =>  1439906738,
                'ip'        =>  '98.221.66.177',
                'type'      =>  'E',
                'id'        =>  'en:bible:notes:1ch:02:01',
                'user'      =>  'craig_oliver',
                'sum'       =>  '[Links:]',
                'extra'     =>  '',
                'file_type' =>  'file'
            ),
            array(
                'date'      =>  1439906688,
                'ip'        =>  '98.221.66.177',
                'type'      =>  'E',
                'id'        =>  'en:bible:notes:1ch:01:01',
                'user'      =>  'craig_oliver',
                'sum'       =>  '',
                'extra'     =>  '',
                'file_type' =>  'file'
            ),
            array(
                'date'      =>  1439576361,
                'ip'        =>  '71.46.238.162',
                'type'      =>  'E',
                'id'        =>  'en:bible:notes:1ch:01:01',
                'user'      =>  'craig_oliver',
                'sum'       =>  '[Links:]',
                'extra'     =>  '',
                'file_type' =>  'file'
            )
        );
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals('success', $response['success']);
        $this->assertEquals('', $response['error_message']);
        $this->assertEquals(count($expected), count($response['changes']));
        $this->assertEquals($expected, $response['changes']);
    }

    /**
     * handleAction() should return correct results when give multiple parameters
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testHandleActionShouldReturnCorrectResultsWithMultipleParameters()
    {
        $_GET['media'] = '1';
        $_GET['namespace'] = 'en:obs';
        $_GET['start']  = '1440525030';
        $_GET['end'] = '1440702530';
        $_GET['order'] = 'asc';
        $expected = array(
            array(
                'date'      =>  1440525038,
                'ip'        =>  '65.129.89.43',
                'type'      =>  'E',
                'id'        =>  'en:obs:38',
                'user'      =>  'superdav42',
                'sum'       =>  '',
                'extra'     =>  '',
                'file_type' =>  'file'
            ),
            array(
                'date'      =>  1440702526,
                'ip'        =>  '127.0.0.1',
                'type'      =>  'C',
                'id'        =>  'en:obs:photo.jpg',
                'user'      =>  'admin',
                'sum'       =>  'created',
                'extra'     =>  '',
                'file_type' =>  'media'
            )
        );
        $this->plugin->handleAction($this->eventHandler, array());
        $response = json_decode($this->plugin->response, true);
        $this->assertEquals('success', $response['success']);
        $this->assertEquals('', $response['error_message']);
        $this->assertEquals(count($expected), count($response['changes']));
        $this->assertEquals($expected, $response['changes']);
    }

    /**
     * isPresent() should return false on empty strings
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testIsPresentShouldReturnFalseIfNotPresent()
    {
        $this->assertFalse($this->plugin->isPresent(null));
        $this->assertFalse($this->plugin->isPresent(''));
    }

    /**
     * isPresent() should return true if it is present
     *
     * @return void
     *
     * @author Johnathan Pulos <johnathan@missionaldigerati.org>
     */
    public function testIsPresentShouldReturnTrueIfPresent()
    {
        $this->assertTrue($this->plugin->isPresent('I am here.'));
    }
}
