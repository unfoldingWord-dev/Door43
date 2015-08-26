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
