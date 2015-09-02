<?php
/**
 * Name: PopulateOBSNotes.test.php
 * Description: Tests for the PopulateOBS notes action plugin.
 *
 * Author: Phil Hopper
 * Date:   2015-08-10
 */

class PopulateOBSNotes_plugin_test extends DokuWikiTest {

	private $obsSrcDir;
	private $destNsDir;

	private static $srcNs = 'en';
	private static $destNs = 'x-obs-notes-unit-test';

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
	}

	public function setUp() {
		$this->pluginsEnabled[] = 'include';
		$this->pluginsEnabled[] = 'door43translation';
		$this->pluginsEnabled[] = 'door43shared';
		$this->pluginsEnabled[] = 'door43obs';

		/** @var $INPUT input */
		global $INPUT;
		$INPUT->get->set('sourceLang', self::$srcNs);
		$INPUT->get->set('destinationLang', self::$destNs);

		// create source files in ./pages/en/obs/notes
		$this->obsSrcDir = TMP_DIR . '/data/pages/' . self::$srcNs . '/obs';
		if (!is_dir($this->obsSrcDir)) mkdir($this->obsSrcDir, 0755, true);

		file_put_contents($this->obsSrcDir . '/notes.txt', 'notes.txt');

		$notesSrc = $this->obsSrcDir . '/notes';
		if (!is_dir($notesSrc)) mkdir($notesSrc, 0755, true);
		for ($i = 1; $i < 51; $i++) {
			file_put_contents($notesSrc . '/' . str_pad($i, 2, '0', STR_PAD_LEFT) . '.txt', $i);
		}

		file_put_contents($notesSrc . '/home.txt', 'home.txt');
		file_put_contents($notesSrc . '/key-terms.txt', 'key-terns.txt');
		file_put_contents($notesSrc . '/questions.txt', 'questions.txt');
		file_put_contents($notesSrc . '/sidebar.txt', 'sidebar.txt');

		// create source files in ./pages/en/obs/notes/frames
		if (!is_dir($notesSrc . '/frames')) mkdir($notesSrc . '/frames', 0755, true);
		file_put_contents($notesSrc . '/frames/01-01.txt', '01-01.txt');
		file_put_contents($notesSrc . '/frames/50-17.txt', '50-17.txt');

		// create source files in ./pages/en/obs/notes/questions
		if (!is_dir($notesSrc . '/questions')) mkdir($notesSrc . '/questions', 0755, true);
		file_put_contents($notesSrc . '/questions/01.txt', '01.txt');
		file_put_contents($notesSrc . '/questions/50.txt', '50.txt');
		file_put_contents($notesSrc . '/questions/home.txt', 'home.txt');

		// obe/kt
		$obeSrc = TMP_DIR . '/data/pages/' . self::$srcNs . '/obe';
		if (!is_dir($obeSrc . '/kt')) mkdir($obeSrc . '/kt', 0755, true);
		file_put_contents($obeSrc . '/home.txt', 'home.txt');
		file_put_contents($obeSrc . '/ktobs.txt', 'ktobs.txt');
		file_put_contents($obeSrc . '/kt/kt-one.txt', 'kt-one.txt');
		file_put_contents($obeSrc . '/kt/kt-two.txt', 'kt-two.txt');
		file_put_contents($obeSrc . '/kt/home.txt', 'home.txt');

		// obe/other
		if (!is_dir($obeSrc . '/other')) mkdir($obeSrc . '/other', 0755, true);
		file_put_contents($obeSrc . '/other/other-one.txt', 'other-one.txt');
		file_put_contents($obeSrc . '/other/other-two.txt', 'other-two.txt');
		file_put_contents($obeSrc . '/other/home.txt', 'home.txt');

		// bible/notes
		$bibleSrc = TMP_DIR . '/data/pages/' . self::$srcNs . '/bible/notes';
		if (!is_dir($bibleSrc)) mkdir($bibleSrc, 0755, true);
		file_put_contents($bibleSrc . '/bible-notes.txt', 'bible-notes.txt');
		file_put_contents($bibleSrc . '/home.txt', 'home.txt');

		if (!is_dir($bibleSrc . '/gen/01')) mkdir($bibleSrc . '/gen/01', 0755, true);
		if (!is_dir($bibleSrc . '/gen/50')) mkdir($bibleSrc . '/gen/50', 0755, true);
		file_put_contents($bibleSrc . '/gen/home.txt', 'home.txt');
		file_put_contents($bibleSrc . '/gen/01/01.txt', '01.txt');
		file_put_contents($bibleSrc . '/gen/50/01.txt', '50.txt');

		// create target namespace in ./pages/x-unit-test
		$this->destNsDir = TMP_DIR . '/data/pages/' . self::$destNs;
		if (!is_dir($this->destNsDir)) mkdir($this->destNsDir, 0755, true);

		parent::setUp();
	}

	public function test_initialize_obs_notes() {

		// TODO: fix this test
		$this->markTestSkipped('The test needs fixed');

		/** @var $thisPlugin action_plugin_door43obs_PopulateOBS */
		$thisPlugin = plugin_load('action', 'door43obs_PopulateOBS');

		if (ob_get_contents()) ob_clean();

		$thisPlugin->initialize_obs_notes();

		$result = ob_get_clean();

		// test the return value
		$expect = sprintf($thisPlugin->getLang('obsNotesCreatedSuccess'), self::$destNs, '/' . self::$destNs . '/obs/notes');
		$this->assertEquals($expect, $result);

		// check for files
		$this->assertFileExists($this->destNsDir . '/obs/notes/home.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/sidebar.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/01.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/50.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/frames/01-01.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/frames/50-17.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/questions/01.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/questions/50.txt');
		$this->assertFileExists($this->destNsDir . '/obs/notes/questions/home.txt');

		$this->assertFileExists($this->destNsDir . '/obe/home.txt');
		$this->assertFileExists($this->destNsDir . '/obe/ktobs.txt');
		$this->assertFileExists($this->destNsDir . '/obe/kt/home.txt');
		$this->assertFileExists($this->destNsDir . '/obe/kt/kt-one.txt');
		$this->assertFileExists($this->destNsDir . '/obe/kt/kt-two.txt');
		$this->assertFileExists($this->destNsDir . '/obe/other/home.txt');
		$this->assertFileExists($this->destNsDir . '/obe/other/other-one.txt');
		$this->assertFileExists($this->destNsDir . '/obe/other/other-two.txt');

		$this->assertFileExists($this->destNsDir . '/bible/notes/home.txt');
		$this->assertFileExists($this->destNsDir . '/bible/notes/bible-notes.txt');
		$this->assertFileExists($this->destNsDir . '/bible/notes/gen/home.txt');
		$this->assertFileExists($this->destNsDir . '/bible/notes/gen/01/01.txt');
		$this->assertFileExists($this->destNsDir . '/bible/notes/gen/50/01.txt');
	}
}
