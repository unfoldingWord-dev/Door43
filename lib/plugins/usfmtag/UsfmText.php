<?php
/**
 * Copyright (c) 2011 Rusmin Soetjipto
 * Ported to Dokuwiki by Yvonne Lu
 * 
 * 1/30/14 the following functions are ported
 *  renderGeneralCommand
 *  switchListLevel
 *  setAlternateChapterNumber
 *  setPublishedChapterNumber
 *  setAlternateVerseNumber
 *  all functions from the original UsfmText.php should be ported
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * 7-25-14 Yvonne Lu 
 * changed function newAnchorLabel to generate number instead of letter labels
 * 
 * 8-6-14 Yvonne Lu
 * commented out some code related to is_verse_popups_extension_available
 * 
 * 8-8-14 Yvonne Lu
 * generating footnote number starting at 1 instead of 0
 * 
 * 1-14-15 YvonneLu
 * Added popup window for footnotes
 * 
 * 3-18-15 Yvonne Lu
 * Took out link to stylesheet in getAndClearHtmlText.  It should take place
 * in the header.
 *
 */

/* yil porting notes:
 * parser needs to do:
 * recursiveTagParse
 */
class UsfmBodyOrFooter {
  private $html_text = '';
  private $is_verse_popups_extension_available = False;
  private $parser;
  const BIBLE_VERSE_REFERENCE_PATTERN_1 = 
    "/\\b([1-3]\\s)?[A-Z][a-z]+\\.?\\s\\d+([\\:\\.]\d+)?([\\-\\~]\\d+)?";
  const BIBLE_VERSE_REFERENCE_PATTERN_2 =
    "([\\;\\,]\\s?\\d+([\\:\\.]\d+)?([\\-\\~]\\d+)?)*/";
  
  //function __construct($parser) {
  //yil no parser for now, also,no verse popup extension (don't know what it is)
  function __construct() {
    $this->is_verse_popups_extension_available = False;  
    
  }
  
   function printHtmlText($html_text) {
      //echo "&quot$html_text&quot<br>";
       $this->html_text .= $html_text;
    /*   
    $final_text = '';
    if ($this->is_verse_popups_extension_available) {
      //global $wgOut;
      while (preg_match(self::BIBLE_VERSE_REFERENCE_PATTERN_1.
                        self::BIBLE_VERSE_REFERENCE_PATTERN_2,
                        $html_text, $matches, PREG_OFFSET_CAPTURE))   
      {
      	$reference_text = "<vs>".$matches[0][0]."</vs>";
        $final_text .= substr($html_text, 0, $matches[0][1]).
                       $this->parser->recursiveTagParse($reference_text);
        $html_text = substr($html_text, 
                            $matches[0][1] + strlen($matches[0][0]));
      }
    }
    $final_text = str_replace("~", "&nbsp;", $final_text.$html_text);
    $this->html_text .= $final_text;*/
  }
  
  function getAndClearHtmlText() {
    $result = $this->html_text;
    $this->html_text = '';
    return $result;
  }
}

class UsfmText {
    private $book_name = '';
    private $is_book_started = False;
    private $latest_chapter_number = 0;

    private $chapter_label = '';
    private $chapter_number = '';
    private $alternate_chapter_number = '';
    private $published_chapter_number = '';
    private $is_current_chapter_using_label = False;

    private $verse_number = '';
    private $alternate_verse_number = '';

    private $table_data = array ();
    private $is_in_table_mode = False;
    const IS_HEADER = 0;
    const IS_RIGHT_ALIGNED = 1;
    const CONTENT_TEXT = 2;

    private $paragraph_state;
    private $body;
    private $footer;
    private $is_in_footer_mode = False;

    private $anchor_count = -1;
    
    private $flush_paragraph_settings = array (
    "default" => "usfm-flush"
    );
    private $drop_cap_numeral_settings = array (
      "usfm-indent" => "usfm-c-indent",
      "default"     => "usfm-c"
    );
    private $pre_chapter_paragraph_classes =
      array("usfm-desc");
  
    const INDENT_LEVEL = 0;
    const IS_ITALIC = 1;
    const ALIGNMENT = 2;
    const PARAGRAPH_CLASS = 3;
    private $default_paragraph = 
        array (0, False, 'justify', 'usfm-indent'); 

    //yil no parser yet
    //118
    function __construct() {
        
        $this->paragraph_state = new UsfmParagraphState();
        $this->body = new UsfmBodyOrFooter(); //yil no parser for now
        $this->footer = new UsfmBodyOrFooter(); //yil no parser for now
    }
    
    //124
    private function getSetting($key, $settings) {
            if (array_key_exists($key, $settings)) {
                    return $settings[$key];
            } else {
                    return $settings["default"];
            }
      }

    //132  
    function setChapterLabel($chapter_label) {
        if ( ($this->chapter_number <> '') ||
             ($this->alternate_chapter_number <> '') ||
             ($this->published_chapter_number <> '') ||
             ($this->is_book_started) )
        {
          $this->chapter_label = $chapter_label;
          $this->is_current_chapter_using_label = True;
        } else {
          $this->setBookName($chapter_label);
        }
      }
   //145
   function setChapterNumber($chapter_number) {
        $this->chapter_number = $chapter_number;
        $this->latest_chapter_number = $chapter_number;
      }
      
   //150
   function setAlternateChapterNumber($alternate_chapter_number) {
        $this->alternate_chapter_number = $alternate_chapter_number;
   }
   
   //154
   function setPublishedChapterNumber($published_chapter_number) {
    $this->published_chapter_number = $published_chapter_number;
   }
   
   //158
    private function getFullChapterNumber() {
        if ($this->chapter_number && $this->alternate_chapter_number) {
          return $this->chapter_number."(".
                 $this->alternate_chapter_number.")";
        } elseif ($this->chapter_number) {
          return $this->chapter_number;
        } else {
          return $this->alternate_chapter_number;
        }
    }
    
   //169
   private function isDropCapNumeralPending() {
  	return ($this->published_chapter_number <> '') ||
  	       ($this->getFullChapterNumber() <> '');
   }
   
   //174
    function flushPendingDropCapNumeral($is_no_break) {
        $final_chapter_number = $this->published_chapter_number ?
                                $this->published_chapter_number :
                                $this->getFullChapterNumber(); 
        if ($final_chapter_number) {
          $this->chapter_number = '';
          $this->alternate_chapter_number = '';
          $this->published_chapter_number = '';
          if ( $is_no_break || ( (!$this->book_name) && 
               (!$this->is_current_chapter_using_label) ) )
          {
            $drop_cap_numeral_class = 
              $this->getSetting($this->paragraph_state->getParagraphClass(),
                                $this->drop_cap_numeral_settings);
            $this->body
                 ->printHtmlText("<span class='".$drop_cap_numeral_class."'>".
                                "<big class='usfm-c'><big class='usfm-c'>".
                                "<big class='usfm-c'><big class='usfm-c'>".
                                $final_chapter_number.
                                "</big></big></big></big></span>");
          }
        }
        $this->is_current_chapter_using_label = False;
      }
      
   //199
    private function flushPendingChapterLabel() {    
    if ($this->chapter_label) {
      $this->body
           ->printHtmlText($this->paragraph_state
                                ->printTitle(False, 3, False,
                                             $this->chapter_label));
      $this->chapter_label = '';   
    } elseif ($this->book_name) {
      $label_text = $this->book_name." ".
                    $this->getFullChapterNumber();
      $this->body->printHtmlText($this->paragraph_state
                                      ->printTitle(False, 3, False,
                                                   $label_text));
    }
  }
  
  //221
   function printTitle($level, $is_italic, $content) {
    $this->body->printHtmlText($this->paragraph_state
                                    ->closeParagraph());
    $this->flushPendingChapterLabel();
    $this->body->printHtmlText($this->paragraph_state
                                    ->printTitle(False, $level, 
                                                 $is_italic,
                                                 $content));
  }
      
   //226
   function switchParagraph($new_indent_level, $is_italic, $alignment, 
                           $paragraph_class)
    {    
      $this->body->printHtmlText($this->paragraph_state
                                      ->closeParagraph());
      $this->flushPendingChapterLabel();
      $is_pre_chapter_paragraph = 
        (False !== array_search($paragraph_class, 
                                $this->pre_chapter_paragraph_classes));

      /* yil commented out debug statement
      wfDebug("switchParagraph: ".($is_pre_chapter_paragraph ? "T" : "F").
              " ".$paragraph_class."\n");*/
      if ( (!$is_pre_chapter_paragraph) &&
           $this->isDropCapNumeralPending() )
      {
        $paragraph_class = 
          $this->getSetting($this->paragraph_state->getParagraphClass(),
                            $this->flush_paragraph_settings);         	
      }
      $this->body->printHtmlText($this->paragraph_state
                                      ->switchParagraph($new_indent_level,
                                                        $is_italic,
                                                        $alignment,
                                                        $paragraph_class));           
      if (!$is_pre_chapter_paragraph) {
        $this->flushPendingDropCapNumeral(False);
      }

    }

    //255
    function setVerseNumber($verse_number) {
        $this->verse_number = $verse_number;
    }
    
    //261
    function setAlternateVerseNumber($alternate_verse_number) {
        $this->alternate_verse_number = $alternate_verse_number;
    }
    
    //266
    private function flushPendingVerseInfo() {
        if ( ($this->alternate_verse_number <> '') || 
             ($this->verse_number <> '') )
        {
            if (!$this->paragraph_state->isOpen()) {
                    $this->switchParagraph($this->default_paragraph[self::INDENT_LEVEL],
                                           $this->default_paragraph[self::IS_ITALIC],
                                           $this->default_paragraph[self::ALIGNMENT],
                                           $this->default_paragraph[self::PARAGRAPH_CLASS]);
            }
          $anchor_verse = $this->verse_number ? $this->verse_number :
                                                $this->alternate_verse_number;
          if ( ($this->alternate_verse_number <> '') &&
               ($this->verse_number <> '') )
          {
            $verse_label = $this->verse_number." (".$this->alternate_verse_number.")";     	
          } else {
            $verse_label = $anchor_verse;
          }
          $this->body->printHtmlText(" <span class='usfm-v'><b class='usfm'>".
                                     "<a name='".$this->latest_chapter_number."_".
                                     $anchor_verse."'></a>".$verse_label.
                                     "</b></span>");
          $this->verse_number = '';
          $this->alternate_verse_number = '';
        }
    }
    
    //294
    function insertTableColumn($is_header, $is_right_aligned, $text) {
        //yil commented out debug statement
        //wfDebug("inserting table column: ".$text."\n");
  	$this->table_data[] = array ($is_header, $is_right_aligned, 
                                 $text);
    }
    
    //301
    function flushPendingTableColumns() {
        
        if (!$this->is_in_table_mode) {
          $this->is_in_table_mode = True;
          $this->body->printHtmlText("\n<table class='usfm'>");
        }
        if (count($this->table_data) > 0) {
          $this->body->printHtmlText("\n<tr class='usfm'>");
            foreach ($this->table_data as $data) {
            $html_text = 
              "\n<td class='usfm-".($data[self::IS_HEADER] ? 'th' : 'tc').
              ($data[self::IS_RIGHT_ALIGNED] ? "' align='right" : "").
              "'>".$data[self::CONTENT_TEXT]."</td>\n";
            $this->body->printHtmlText($html_text);
          }
          $this->table_data = array ();
        }
    }
    
    //320
    function printHtmlTextToBody($html_text) {
        $this->is_book_started = True;
        if ($this->is_in_table_mode) {
          $this->flushPendingTableColumns();
          $this->body->printHtmlText("\n</table>\n");
          $this->is_in_table_mode = False;
        }
        $this->flushPendingVerseInfo();

        $this->body->printHtmlText($html_text);
    }
    //332
    function printItalicsToBody($if_normal, $if_italic_paragraph) {
        if ($this->paragraph_state->isItalic()) {
          $this->printHtmlTextToBody($if_italic_paragraph);
        } else {
          $this->printHtmlTextToBody($if_normal);
        }
      }
    
    //340
    function printHtmlTextToFooter($html_text) {
        $this->footer->printHtmlText($html_text);
    }  
    
    //347
    function printHtmlText($html_text) {
        if ($this->is_in_footer_mode) {
          $this->printHtmlTextToBody($html_text);  //YIL added text for popup window
          $this->printHtmlTextToFooter($html_text);
        } else {
          $this->printHtmlTextToBody($html_text);
        }
    }
    //355 
    function switchListLevel($new_list_level) {
        $this->printHtmlTextToBody($this->paragraph_state
                                        ->switchListLevel($new_list_level));
      }  
    
    //1-14-15 added popup window for footnote
    function newFooterEntry() {
        $this->is_in_footer_mode = True;
        $anchor_label = $this->newAnchorLabel();
        /*
        $this->printHtmlTextToBody("<span class='usfm-f1'>[<a name='".
                                   $anchor_label."*' href='#".$anchor_label.
                                   "'>".$anchor_label."</a>]</span> ");*/
        $this->printHtmlTextToBody("<span class='popup_marker'>"
                                   ."<span class='usfm-f1'>[<a name='".
                                   $anchor_label."*' href='#".$anchor_label.
                                   "'>".$anchor_label."</a>]</span>" 
                                   ."<span class='popup'>");
        
        $this->printHtmlTextToFooter("<p class='usfm-footer'>".
                                     "<span class='usfm-f2'>[<a name='".
                                     $anchor_label."' href='#".$anchor_label.
                                     "*'>".$anchor_label."</a>]</span> ");
      }
  
    //yil this function origianlly generates letter footnote labels.  
    //It's changed to number so 
    //that international users can have an easier time to read it.      
    private function newAnchorLabel() {
      $count = ++$this->anchor_count;
      $anchor_label = strval(($count+1)); //generating footnote number starting at 1 instead of 0
      /* yil original letter generating label code
      $anchor_label = '';
      do {
        $anchor_label = chr(ord('a') + ($count % 26)) . $anchor_label;
        $count = (int) floor($count / 26);
      } while ($count > 0);*/
      return $anchor_label;
    }
  
    
    function closeFooterEntry() {
      //end popup
      $this->printHtmlTextToBody("</span></span>"); //added popup window end tags
      
      $this->is_in_footer_mode = False;
      $this->printHtmlTextToFooter("</p>");
    }
  
    
    function getAndClearHtmlText() {
  	
              $this->printHtmlTextToBody('');
              /*
              return "<link rel='stylesheet' href='lib".DIRECTORY_SEPARATOR."plugins".DIRECTORY_SEPARATOR.
                                                "usfmtag".DIRECTORY_SEPARATOR."style.css'".
                   " type='text/css'>".  
                   $this->body->getAndClearHtmlText().     
               $this->paragraph_state
                    ->printTitle(True, 4, False, "").
               $this->footer->getAndClearHtmlText();*/
              
              
               
               return $this->body->getAndClearHtmlText().     
               $this->paragraph_state
                    ->printTitle(True, 4, False, "").
               $this->footer->getAndClearHtmlText();
    }
    
    
    //YIL added this function to corrent indent bug
    function closeParagraph() {
        $this->body->printHtmlText($this->paragraph_state
                                    ->closeParagraph());  
    }
  
  
  
  
}
?>