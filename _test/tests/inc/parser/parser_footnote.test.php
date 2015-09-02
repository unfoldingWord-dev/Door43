<?php
require_once 'parser.inc.php';

class TestOfDoku_Parser_Footnote extends TestOfDoku_Parser {

    function setUp() {
        parent::setUp();
        $this->P->addMode('footnote',new Doku_Parser_Mode_Footnote());
    }

    function testFootnote() {
        $this->P->parse('Foo (( testing )) Bar');
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' testing ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testNotAFootnote() {
        $this->P->parse("Foo (( testing\n Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\nFoo (( testing\n Bar")),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteLinefeed() {
        $this->P->addMode('eol',new Doku_Parser_Mode_Eol());
        $this->P->parse("Foo (( testing\ntesting )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array('Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(" testing\ntesting ")),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteNested() {
        $this->P->parse('Foo (( x((y))z )) Bar');
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' x((y')),
              array('footnote_close',array()),
            ))),
            array('cdata',array('z )) Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteEol() {
        $this->P->addMode('eol',new Doku_Parser_Mode_Eol());
        $this->P->parse("Foo \nX(( test\ning ))Y\n Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array('Foo '.DOKU_PARSER_EOL.'X')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(" test\ning ")),
              array('footnote_close',array()),
            ))),
            array('cdata',array('Y'.DOKU_PARSER_EOL.' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteStrong() {
        $this->P->addMode('strong',new Doku_Parser_Mode_Formatting('strong'));
        $this->P->parse('Foo (( **testing** )) Bar');
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' ')),
              array('strong_open',array()),
              array('cdata',array('testing')),
              array('strong_close',array()),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteHr() {
        $this->P->addMode('hr',new Doku_Parser_Mode_HR());
        $this->P->parse("Foo (( \n ---- \n )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' ')),
              array('hr',array()),
              array('cdata',array("\n ")),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteCode() {
        $this->P->addMode('code',new Doku_Parser_Mode_Code());
        $this->P->parse("Foo (( <code>Test</code> )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' ')),
              array('code',array('Test',null,null)),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnotePreformatted() {
        $this->P->addMode('preformatted',new Doku_Parser_Mode_Preformatted());
        $this->P->parse("Foo (( \n  Test\n )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' ')),
              array('preformatted',array('Test')),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnotePreformattedEol() {
        $this->P->addMode('preformatted',new Doku_Parser_Mode_Preformatted());
        $this->P->addMode('eol',new Doku_Parser_Mode_Eol());
        $this->P->parse("Foo (( \n  Test\n )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array('Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' ')),
              array('preformatted',array('Test')),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );

        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteUnformatted() {
        $this->P->addMode('unformatted',new Doku_Parser_Mode_Unformatted());
        $this->P->parse("Foo (( <nowiki>Test</nowiki> )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' ')),
              array('unformatted',array('Test')),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteNotHeader() {
        $this->P->addMode('unformatted',new Doku_Parser_Mode_Unformatted());
        $this->P->parse("Foo (( \n====Test====\n )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(" \n====Test====\n ")),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteTable() {
        $this->P->addMode('table',new Doku_Parser_Mode_Table());
        $this->P->parse("Foo ((
| Row 0 Col 1    | Row 0 Col 2     | Row 0 Col 3        |
| Row 1 Col 1    | Row 1 Col 2     | Row 1 Col 3        |
 )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('table_open',array(3, 2, 8)),
              array('tablerow_open',array()),
              array('tablecell_open',array(1,'left',1)),
              array('cdata',array(' Row 0 Col 1    ')),
              array('tablecell_close',array()),
              array('tablecell_open',array(1,'left',1)),
              array('cdata',array(' Row 0 Col 2     ')),
              array('tablecell_close',array()),
              array('tablecell_open',array(1,'left',1)),
              array('cdata',array(' Row 0 Col 3        ')),
              array('tablecell_close',array()),
              array('tablerow_close',array()),
              array('tablerow_open',array()),
              array('tablecell_open',array(1,'left',1)),
              array('cdata',array(' Row 1 Col 1    ')),
              array('tablecell_close',array()),
              array('tablecell_open',array(1,'left',1)),
              array('cdata',array(' Row 1 Col 2     ')),
              array('tablecell_close',array()),
              array('tablecell_open',array(1,'left',1)),
              array('cdata',array(' Row 1 Col 3        ')),
              array('tablecell_close',array()),
              array('tablerow_close',array()),
              array('table_close',array(123)),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteList() {
        $this->P->addMode('listblock',new Doku_Parser_Mode_ListBlock());
        $this->P->parse("Foo ((
  *A
    * B
  * C
 )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('listu_open',array()),
              array('listitem_open',array(1,Doku_Handler_List::NODE)),
              array('listcontent_open',array()),
              array('cdata',array("A")),
              array('listcontent_close',array()),
              array('listu_open',array()),
              array('listitem_open',array(2)),
              array('listcontent_open',array()),
              array('cdata',array(' B')),
              array('listcontent_close',array()),
              array('listitem_close',array()),
              array('listu_close',array()),
              array('listitem_close',array()),
              array('listitem_open',array(1)),
              array('listcontent_open',array()),
              array('cdata',array(' C')),
              array('listcontent_close',array()),
              array('listitem_close',array()),
              array('listu_close',array()),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );
        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteQuote() {
        $this->P->addMode('quote',new Doku_Parser_Mode_Quote());
        $this->P->parse("Foo ((
> def
>>ghi
 )) Bar");
        $calls = array (
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n".'Foo ')),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('quote_open',array()),
              array('cdata',array(" def")),
              array('quote_open',array()),
              array('cdata',array("ghi")),
              array('quote_close',array()),
              array('quote_close',array()),
              array('cdata',array(' ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(' Bar')),
            array('p_close',array()),
            array('document_end',array()),
        );

        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }

    function testFootnoteNesting() {
        $this->P->addMode('strong',new Doku_Parser_Mode_Formatting('strong'));
        $this->P->parse("(( a ** (( b )) ** c ))");

        $calls = array(
            array('document_start',array()),
            array('p_open',array()),
            array('cdata',array("\n")),
            array('nest', array ( array (
              array('footnote_open',array()),
              array('cdata',array(' a ')),
              array('strong_open',array()),
              array('cdata',array(' (( b ')),
              array('footnote_close',array()),
            ))),
            array('cdata',array(" ")),
            array('strong_close',array()),
            array('cdata',array(" c ))")),
            array('p_close',array()),
            array('document_end',array()),
        );

        $this->assertEquals(array_map('stripbyteindex',$this->H->calls),$calls);
    }
}

