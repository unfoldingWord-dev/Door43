<?php

/**
 * Tests for requesting revisioninfo of a revision of a page with getRevisionInfo()
 *
 * This class uses the files:
 * - data/pages/mailinglist.txt
 * - data/meta/mailinglist.changes
 */
class changelog_getlastrevisionat_test extends DokuWikiTest {

    private $pageid = 'mailinglist';
    
    function setup() {
        parent::setup();
        global $cache_revinfo;
        $cache =& $cache_revinfo;
        if(isset($cache['nonexist'])) {
            unset($cache['nonexist']);
        }
        if(isset($cache['mailinglist'])) {
            unset($cache['mailinglist']);
        }
    }
    

    /**
     * no nonexist.changes meta file available
     */
    function test_changemetadatanotexists() {
        $rev = 1362525899;
        $id = 'nonexist';
        $revsexpected = false;

        $pagelog = new PageChangeLog($id, $chunk_size = 8192);
        $revs = $pagelog->getLastRevisionAt($rev);
        $this->assertEquals($revsexpected, $revs);
    }

    /**
     * start at exact current revision of mailinglist page
     *
     */
    function test_startatexactcurrentrev() {
        $rev = 1385051947;
        $revsexpected = '';

        //set a known timestamp
        touch(wikiFN($this->pageid), $rev);

        $pagelog = new PageChangeLog($this->pageid, $chunk_size = 8192);
        $revs = $pagelog->getLastRevisionAt($rev);
        $this->assertEquals($revsexpected, $revs);

    }
    
    /**
     * test a future revision
     *
     */
    function test_futurerev() {
        $rev = 1385051947;
        $revsexpected = '';

        //set a known timestamp
        touch(wikiFN($this->pageid), $rev);
        
        $rev +=1;

        $pagelog = new PageChangeLog($this->pageid, $chunk_size = 8192);
        $revs = $pagelog->getLastRevisionAt($rev);
        $this->assertEquals($revsexpected, $revs);

    }

    /**
     * start at exact last revision of mailinglist page
     *
     */
    function test_exactlastrev() {
        $rev = 1360110636;
        $revsexpected = 1360110636;

        $pagelog = new PageChangeLog($this->pageid, $chunk_size = 8192);
        $revs = $pagelog->getLastRevisionAt($rev);
        $this->assertEquals($revsexpected, $revs);
    }


    /**
     * Request not existing revision
     * 
     */
    function test_olderrev() {
        $rev = 1;
        $revexpected = false;

        $pagelog = new PageChangeLog($this->pageid, $chunk_size = 8192);
        $revfound = $pagelog->getLastRevisionAt($rev);
        $this->assertEquals($revexpected, $revfound);
    }

    /**
     * Start at non existing revision somewhere between existing revisions
     */
    function test_notexistingrev() {
        $rev = 1362525890;
        $revexpected = 1362525359;

        $pagelog = new PageChangeLog($this->pageid, $chunk_size = 8192);
        $revfound = $pagelog->getLastRevisionAt($rev);
        $this->assertEquals($revexpected, $revfound);
    }

    /**
     * request nonexisting page
     *
     */
    function test_notexistingpage() {
        $rev = 1385051947;
        $currentexpected = false;

        $pagelog = new PageChangeLog('nonexistingpage', $chunk_size = 8192);
        $current = $pagelog->getLastRevisionAt($rev);
        $this->assertEquals($currentexpected, $current);
    }
}