<?php
require_once 'parser.inc.php';

/**
 * Tests for the implementation of audio and video files
 *
 * @author  Michael Große <grosse@cosmocode.de>
*/
class TestOfDoku_Parser_Media extends TestOfDoku_Parser {

    function testVideoOGVExternal() {
        $file = 'http://some.where.far/away.ogv';
        $parser_response = p_get_instructions('{{' . $file . '}}');

        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('externalmedia',array($file,null,null,null,null,'cache','details')),
            array('cdata',array(null)),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$parser_response),$calls);

        $Renderer = new Doku_Renderer_xhtml();
        $url = $Renderer->externalmedia($file,null,null,null,null,'cache','details',true);
        //print_r("url: " . $url);
        $video = '<video class="media" width="320" height="240" controls="controls">';
        $this->assertEquals(substr($url,0,66),$video);
        $source = '<source src="http://some.where.far/away.ogv" type="video/ogg" />';
        $this->assertEquals(substr($url,67,64),$source);
        // work around random token
        $a_first_part = '<a href="/tmp/lib/exe/fetch.php?cache=&amp;tok=';
        $a_second_part = '&amp;media=http%3A%2F%2Fsome.where.far%2Faway.ogv" class="media mediafile mf_ogv" title="http://some.where.far/away.ogv">';
        $this->assertEquals($a_first_part, substr($url,132,47));
        $this->assertEquals($a_second_part, substr($url,185,121));
        $rest = 'away.ogv</a></video>'."\n";
        $this->assertEquals($rest, substr($url,306));
    }

    /**
     * unknown extension of external media file
     */
    function testVideoVIDExternal() {
        $file = 'http://some.where.far/away.vid';
        $parser_response = p_get_instructions('{{' . $file . '}}');

        $calls = array(
            array('document_start', array()),
            array('p_open', array()),
            array('externalmedia', array($file, null, null, null, null, 'cache', 'details')),
            array('cdata', array(null)),
            array('p_close', array()),
            array('document_end', array()),
        );
        $this->assertEquals(array_map('stripbyteindex', $parser_response), $calls);

        $Renderer = new Doku_Renderer_xhtml();
        $url = $Renderer->externalmedia($file, null, null, null, null, 'cache', 'details', true);
        // work around random token
        $a_first_part = '<a href="/tmp/lib/exe/fetch.php?tok=';
        $a_second_part = '&amp;media=http%3A%2F%2Fsome.where.far%2Faway.vid" class="media mediafile mf_vid" title="http://some.where.far/away.vid">';
        $this->assertEquals($a_first_part, substr($url,0,36));
        $this->assertEquals($a_second_part, substr($url,42,121));
        $rest = 'away.vid</a>';
        $this->assertEquals($rest, substr($url,163));
    }


    function testVideoOGVInternal() {
        $file = 'wiki:kind_zu_katze.ogv';
        $parser_response = p_get_instructions('{{' . $file . '}}');

        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('internalmedia',array($file,null,null,null,null,'cache','details')),
            array('cdata',array(null)),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$parser_response),$calls);

        $Renderer = new Doku_Renderer_xhtml();
        $url = $Renderer->externalmedia($file,null,null,null,null,'cache','details',true);

        $video = '<video class="media" width="320" height="240" controls="controls" poster="/tmp/lib/exe/fetch.php?media=wiki:kind_zu_katze.png">';
        $this->assertEquals($video, substr($url,0,127));

        $source_webm = '<source src="/tmp/lib/exe/fetch.php?media=wiki:kind_zu_katze.webm" type="video/webm" />';
        $this->assertEquals($source_webm, substr($url,128,87));
        $source_ogv = '<source src="/tmp/lib/exe/fetch.php?media=wiki:kind_zu_katze.ogv" type="video/ogg" />';
        $this->assertEquals($source_ogv, substr($url,216,85));

        $a_webm = '<a href="/tmp/lib/exe/fetch.php?id=&amp;cache=&amp;media=wiki:kind_zu_katze.webm" class="media mediafile mf_webm" title="wiki:kind_zu_katze.webm (99.1 KB)">kind_zu_katze.webm</a>';
        $a_ogv = '<a href="/tmp/lib/exe/fetch.php?id=&amp;cache=&amp;media=wiki:kind_zu_katze.ogv" class="media mediafile mf_ogv" title="wiki:kind_zu_katze.ogv (44.8 KB)">kind_zu_katze.ogv</a>';
        $this->assertEquals($a_webm, substr($url,302,178));
        $this->assertEquals($a_ogv, substr($url,480,174));

        $rest = '</video>'."\n";
        $this->assertEquals($rest, substr($url,654));
    }
}
