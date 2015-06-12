<?php
/**
 * original code by:
 * Copyright (c) 2011 Rusmin Soetjipt
 * Ported to Dokuwiki by Yvonne Lu 2013
 * 
 * 10/6/14 Yvonne Lu
 * Correct indent problem in poetry
 * 
 * 8/13/14 Yvonne Lu
 * Implemented /ip as paragraph
 * Implemented /is and /imt as section headings
 * 
 * 8/6/14 Yvonne Lu
 * translate \s5 to <hr>
 * 
 * Fixed space before punctuation problem for add tags
 * 
 * 7/25/14
 * Disabled formatting for \add tags <jesse@distantshores.org>
 * 
 * 
 * 6/28/14
 * Corrected a bug concerning command parsing.  Punctuation was parsed with the
 * command which caused invalid rendering behavior.  I've noticed that many of the
 * php string functions utilized in the original code are single byte functions.
 * This may cause a problem when the string is in unicode that requires double
 * byte operation. Also, preg_match and ereg_match both hangs my version of 
 * dokuwiki.  As a result, I was not able to use these functions.
 * 
 * 
 * 1/30/14
 * ported function renderOther, renderTable, renderIntroduction to support command
 * 'i', 'it', 'd', 'r', 't', 'tl','x'
 * 
 * 
 * There seems to be a bug in function renderChapterOrVerse for setting 
 * alternate verse number and chapter.  It was using an uninitialized variable, 
 * verse number.  I commented out the action for now.
 * 
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
 */

class UsfmTagDecoder {
    private $usfm_text;

    const BASE_LEVEL = 0;
    const IS_ITALIC = 1;
    const ALIGNMENT = 2;
    const PARAGRAPH_CLASS = 3;
    
     private $paragraph_settings = array (
    // Chapter and Verses
    "cd"  => array (0, True, 'left', 'usfm-desc'),
    // Titles, Headings, and Label
    "d"   => array (0, True, 'left', 'usfm-desc'),
    "sp"  => array (0, True, 'left', 'usfm-flush'),
    // Paragraph and Poetry (w/o level parameter)
    "cls" => array (0, False, 'right', 'usfm-right'),
    "m"   => array (0, False, 'justify', 'usfm-flush'),
    "mi"  => array (1, False, 'justify', 'usfm-flush'),
    "p"   => array (0, False, 'justify', 'usfm-indent'),
    "pc"  => array (0, False, 'center', 'usfm-center'),
    "pm"  => array (1, False, 'justify', 'usfm-indent'),
    "pmc" => array (1, False, 'justify', 'usfm-flush'),
    "pmo" => array (1, False, 'justify', 'usfm-flush'),
    "pmr" => array (1, False, 'right', 'usfm-right'),
    "pr"  => array (0, False, 'right', 'usfm-right'),
    "qa"  => array (1, True, 'center', 'usfm-center'),
    "qc"  => array (1, False, 'center', 'usfm-center'),
    "qr"  => array (1, True, 'right', 'usfm-right'),
    // Paragraph and Poetry (w/ level parameter)
    "ph"  => array (0, False, 'justify', 'usfm-hanging'),
    "pi"  => array (1, False, 'justify', 'usfm-indent'),
    "q"   => array (2, False, 'left', 'usfm-hanging'),
    "qm"  => array (1, True, 'left', 'usfm-hanging'),
    "ip"  => array (0, False, 'justify', 'usfm-indent')
    ); 
    private $title_settings = array (
        // Titles, Headings, and Label (w/o level parameter)
        "mr"  => array (2, True),
        "r"   => array (5, True),
        "sr"  => array (5, True),
        // Titles, Headings, and Label (w/ level parameter)
        "imt" => array (1, False),
        "is"  => array (1, False),
        "mt"  => array (1, False),
        "mte" => array (1, False),
        "ms"  => array (2, False),
        "s"   => array (3, False),    
      ); 
    const IF_NORMAL = 0;
    const IF_ITALIC_PARAGRAPH = 1; 
    private $substitution_table = array (
        // Titles, Headings, and Labels
        "rq"   => array ("\n<span class='usfm-selah'><i class='usfm'>"),
        "rq*"  => array ("</i></span>\n"),
        // Paragraphs and Poetry
        "b"    => array ("\n<br>"),
        "qac"  => array ("<big class='usfm-qac'>"),
        "qac*" => array ("</big>"),
        "qs"   => array ("\n<span class='usfm-selah'><i class='usfm'>"),
        "qs*"  => array ("</i></span>\n"),
        // Cross Reference
        "x"    => array ("\n<span class='usfm-selah'>"),
        "x*"   => array ("</span>\n"),
        // Other
        // 7-25-14 disabled formatting for \add tags <jesse@distantshores.org>
        //"add"  => array ("<i class='usfm'>[", "</i>["),
        //"add*" => array ("]</i>", "]<i class='usfm'>"),
        "add"  => array (" "),
        "add*" => array (""),
        "bk"   => array ("<i class='usfm'>&quot;", "</i>&quot;"),
        "bk*"  => array ("&quot;</i>", "&quot;<i class='usfm'>"),
        "dc"   => array ("<code class='usfm'>"),
        "dc*"  => array ("</code>"),
        "k"    => array ("<code class='usfm'>"),
        "k*"   => array ("</code>"),
        "lit"  => array ("\n<span class='usfm-selah'><b class='usfm'>"),
        "lit*" => array ("</b></span>\n"),
        "ord"  => array ("<sup class='usfm'>"),
        "ord*" => array ("</sup>"),
        "pn*"  => array (""),
        "qt"   => array ("<i class='usfm'>", "</i>"),
        "qt*"  => array ("</i>", "<i class='usfm'>"),
        "s5"   => array ("<hr>"), //Yvonne added 8/6/14
        "sig"  => array ("<i class='usfm'>", "</i>"),
        "sig*" => array ("</i>", "<i class='usfm'>"),
        "sls"  => array ("<i class='usfm'>", "</i>"),
        "sls*" => array ("</i>", "<i class='usfm'>"),
        "tl"   => array ("<i class='usfm'>", "</i>"),
        "tl*"  => array ("</i>", "<i class='usfm'>"),
        "wj"   => array ("<font color='red'"),
        "wj*"  => array ("</font>"),
        "em"   => array ("<i class='usfm'>", "</i>"),
        "em*"  => array ("</i>", "<i class='usfm'>"),
        "bd"   => array ("<b class='usfm'>"),
        "bd*"  => array ("</b>"),
        "it"   => array ("<i class='usfm'>", "</i>"),
        "it*"  => array ("</i>", "<i class='usfm'>"),
        "bdit" => array ("<i class='usfm'><b class='usfm'>", "</i></b>"),
        "bdit*"=> array ("</b></i>", "<b class='usfm'><i class='usfm'>"),
        "no"   => array ("", "</i>"),
        "no*"  => array ("", "<i class='usfm'>"),
        "sc"   => array ("<small class='usfm'>"),
        "sc*"  => array ("</small>"),
        "\\"   => array ("<br>"),
        "skip" => array ("</usfm> <br>~~NO_STYLING~~"),
        "skip*" => array ("<br>~~NO_STYLING~~ <br><usfm>")
      );
    const BEFORE_REMAINING = 0;
    const AFTER_REMAINING = 1;
       
    private $footnote_substitution_table = array (
        // Footnotes
        "fdc" => array ("<i class='usfm'>", ""),
        "fdc*"=> array ("</i>", ""),
        "fl"  => array ("<u class='usfm'>", "</u>"),
        "fm"  => array ("<code class='usfm'>", ""),
        "fm*" => array ("</code>", ""),
        "fp"  => array ("</p>\n<p class='usfm-footer'>", ""),
        "fq"  => array ("<i class='usfm'>", "</i>"),
        "fqa" => array ("<i class='usfm'>", "</i>"),
        "fr"  => array ("<b class='usfm'>", "</b>"),
        "fv"  => array (" <span class='usfm-v'>", "</span>"),
        // Cross References
        "xdc" => array ("<b class='usfm'>", ""),
        "xdc*"=> array ("</b>", ""),
        "xnt" => array ("<b class='usfm'>", ""),
        "xnt*"=> array ("</b>", ""),
        "xot" => array ("<b class='usfm'>", ""),
        "xot*"=> array ("</b>", ""),
        "xo"  => array ("<b class='usfm'>", "</b>"),
        "xq"  => array ("<i class='usfm'>", "</i>")
      );
    
    const MAX_SELAH_CROSS_REFERENCES_LENGTH = 10;
    
    private $is_poetry=false; //yil added this to solve indent problem
    
    
    public function __construct() {
        //yil no parser for now until I find out what it does
        $this->usfm_text = new UsfmText();
        //$this->usfm_text = new UsfmText($parser);
    }
  
    public function decode($raw_text) {
        //wfDebug("Internal encoding: ".mb_internal_encoding());
            //wfDebug("UTF-8 compatible: ".mb_check_encoding($raw_text, "UTF-8"));
        //wfDebug("ISO-8859-1 compatible: ".mb_check_encoding($raw_text, "ISO-8859-1"));
        
        $usfm_segments = explode("\\", $raw_text);
        
            
        for ($i=0; $i<sizeof($usfm_segments); $i++) {
            
            $remaining = strpbrk($usfm_segments[$i], " \n");
            
            /*yil debug
            $this->usfm_text->printHtmlText("<br/>remaining: ");
            $this->usfm_text->printHtmlText($remaining);
            $this->usfm_text->printHtmlText("<br/>");*/
            
            if ($remaining === false) {
              $raw_command = $usfm_segments[$i];
              $remaining = '';
            } else {
              $raw_command = substr($usfm_segments[$i], 0,
                                    strlen($usfm_segments[$i])-
                                    strlen($remaining));
              $remaining = trim($remaining, " \n\t\r\0");
              if ( mb_substr($remaining, mb_strlen($remaining)-1, 1) != "\xA0" ) {
                      $remaining .= " ";
              }
            }
            
            if ($raw_command == '') {
                continue;
            } else {
                //yil fix punctuation appended to command token
                //note:  preg_match and ereg_match hangs my version of dokuwiki for some 
                //reason so I'm not using it here
                $pos = mb_strpos($raw_command, '*');
                $cmdlen= mb_strlen($raw_command);
                
                if ($pos){
                    /* yil debug
                    $this->usfm_text->printHtmlText("<br/>pos=: ".  strval($pos));
                    $this->usfm_text->printHtmlText("<br/>length=: ".  strval(mb_strlen($raw_command)));*/
                    if (($pos+1)<$cmdlen){
                        //$this->usfm_text->printHtmlText("<br/>raw_command=: ".$raw_command);
                        $leftover = mb_substr($raw_command, $pos+1, $cmdlen);
                        //$this->usfm_text->printHtmlText("<br/>leftover=: ".  $leftover);
                        $remaining = $leftover.' '.$remaining;
                        //$this->usfm_text->printHtmlText("<br/>remaining=: ".  $remaining);
                        $raw_command = mb_substr($raw_command, 0, $pos+1);
                        //$this->usfm_text->printHtmlText("<br/>raw_command=: ".$raw_command);
                    }
                    
                }
                
            }
            
            /*yil debug
            $this->usfm_text->printHtmlText("<br/>raw_command: ");
            $this->usfm_text->printHtmlText($raw_command);
            $this->usfm_text->printHtmlText("<br/>");*/
            
            //filter out number digits from raw command
            $main_command_length = strcspn($raw_command, '0123456789');
            $command = substr($raw_command, 0, $main_command_length);
            
            if (strlen($raw_command) > $main_command_length) {
                $level = strval(substr($raw_command, $main_command_length));
            } else {
                $level = 1;
            }
            /*yil debug
            $this->usfm_text->printHtmlText("<br/>command: ");
            $this->usfm_text->printHtmlText($command);
            $this->usfm_text->printHtmlText("<br/>");*/
            
           
            //port it case by case basis  
            if (  ($command == 'h')  || (substr($command, 0, 2) == 'id') ||
            ($command == 'rem')  || ($command == 'sts') ||
            (substr($command, 0, 3) == 'toc')  )
            {
                $this->renderIdentification($command, $level, $remaining);
            }elseif ($command == 'ip'){
          
              $this->renderParagraph($command, $level, $remaining);
              
            }elseif (($command == 'is') || ($command == 'imt')) {
                
              $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);
              
            }elseif (  (substr($command, 0, 1) == 'i') && 
                  (substr($command, 0, 2) <> 'it') ) 
            {
              $this->renderIntroduction($command, $level, $remaining);

            }elseif (  (substr($command, 0, 1) == 'm') && 
                  ($command <> 'm') && ($command <> 'mi')  ) 
            {
              $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);
            }elseif (  (substr($command, 0, 1) == 's') &&
                  (substr($command, 0, 2) <> 'sc') &&
                  (substr($command, 0, 3) <> 'sig') &&
                  (substr($command, 0, 3) <> 'sls')  )
            {
               if ($level==5) {
                    //Yvonne substitue s5 with <hr>
                    $command .=$level;
                    $level=1;
                    $this->renderGeneralCommand($command, $level, $remaining);
               } else { 
                    $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);
               }

            } elseif (  ($command == 'd') || (substr($command, 0, 1) == 'r')  ) {
              $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);
      
            } elseif (   (substr($command, 0, 1) == 'c') || 
                            (substr($command, 0, 1) == 'v')  )
            {
              $this->renderChapterOrVerse($raw_command, $command, 
                                          $level, $remaining);

            } elseif (  (substr($command, 0, 1) == 'q') &&
                  (substr($command, 0, 2) <> 'qt')  )
            {
              $this->renderPoetry($command, $level, $remaining);
            }elseif (  (substr($command, 0, 1) == 'p')  && ($command <> 'pb') && 
                       (substr($command, 0, 2) <> 'pn') &&
                       (substr($command, 0, 3) <> 'pro')  )
            {
              $this->renderParagraph($command, $level, $remaining);
              
            }elseif (  (substr($command, 0, 1) == 't') &&
                  (substr($command, 0, 2) <> 'tl')  )
            {
              $this->renderTable($command, $level, $remaining);
              
            }elseif (  ($command == 'b') || ($command == 'cls') ||
                  (substr($command, 0, 2) == 'li') || 
                  ($command == 'm') || ($command == 'mi') || 
                  ($command == 'nb')  )
            {
              $this->renderParagraph($command, $level, $remaining);
            }elseif (  (substr($command, 0, 1) == 'f') &&
                  (substr($command, 0, 3) <> 'fig')  )
            {
              $this->renderFootnoteOrCrossReference($raw_command, $remaining);
              // located in UsfmTag.3.php

            } elseif (substr($command, 0, 1) == 'x') {
              $this->renderFootnoteOrCrossReference($raw_command, $remaining);
              // located in UsfmTag.3.php
            }else {
              $this->renderOther($raw_command, $remaining);
            } // if 
            
            
            
           
        }//for
      
      return $this->usfm_text->getAndClearHtmlText();  
        
    }
  
    //260
    protected function renderIdentification($command, $level, 
                                          $remaining)
    {
        
        $this->displayUnsupportedCommand($command, $level, $remaining);
    }
    
    //268
    protected function renderIntroduction($command, $level,
                                        $remaining)
    {
      $this->displayUnsupportedCommand($command, $level, $remaining);
    }
    
    //274
    protected function renderTitleOrHeadingOrLabel($command, $level,
                                                 $remaining) 
    {
      $this->renderGeneralCommand($command, $level, $remaining);  
    }
    //280
     protected function renderChapterOrVerse($raw_command, $command, 
                                          $level, $remaining)
    {
          $remaining = trim($remaining, " ");
      if ( (substr($command, 0, 1) == 'v') &&
           (strlen($raw_command) == strlen($command)) ) {
        $level = $this->extractSubCommand($remaining);
      }
      switch ($command) {
      case 'c':
        $this->usfm_text->setChapterNumber($remaining);
        break;
      case 'ca':
        $this->usfm_text->setAlternateChapterNumber($remaining);
        break;
      case 'cl':
        $this->usfm_text->setChapterLabel($remaining);
        break;
      case 'cp':
        $this->usfm_text->setPublishedChapterNumber($remaining);
        break;
      case 'cd':
        $this->switchParagraph($command, $level);
        $this->usfm_text->printHtmlText($remaining);
        break;
      case 'v':
        $this->usfm_text->setVerseNumber($level);
        $this->usfm_text->printHtmlText($remaining);
        break;
      case 'va':
        //yil verse_number is not initialized  
        //$this->usfm_text->setAlternateVerseNumber($verse_number);
        break;
      case 'vp':
        //yil $verse_number is not initialized  
        //$this->usfm_text->setPublishedChapterNumber($verse_number);
        break;
      default:
        $this->usfm_text->printHtmlText($remaining);
      }
    }
  
    
    
    //318
    protected function renderPoetry($command, $level, $remaining) {
        $this->is_poetry = true;
        $this->renderGeneralCommand($command, $level, $remaining);
      }
      
    //yil added case for 'b' to close out paragraph
    protected function renderParagraph($command, $level, $remaining) {
        switch ($command) {
          case 'nb':
            $this->usfm_text->flushPendingDropCapNumeral(True);
            $this->usfm_text->printHtmlText($remaining);
            break;
          case 'li':
            $this->usfm_text->switchListLevel($level);
            $this->usfm_text->printHtmlText("<li class='usfm'>".$remaining);
            break;
          case 'b':
            $this->renderGeneralCommand($command, $level, $remaining);
              
            if ($this->is_poetry){                 
                $result =  $this->switchParagraph('m', 1); 
                $this->is_poetry=false;
            }
            break;
          default:
            $this->renderGeneralCommand($command, $level, $remaining);
        }
    }
    
    //340 
    protected function renderTable($command, $level, $remaining) {
        switch ($command) {
        case 'tr':
          $this->usfm_text->flushPendingTableColumns();
          break;
        case 'th':
          $this->usfm_text->insertTableColumn(True, False, $remaining);
          break;
        case 'thr':
          $this->usfm_text->insertTableColumn(True, True, $remaining);
          break;
        case 'tc':
          $this->usfm_text->insertTableColumn(False, False, $remaining);
          break;
        case 'tcr':
          $this->usfm_text->insertTableColumn(False, True, $remaining);
        }
    }
    
    //358
    protected function renderFootnoteOrCrossReference($command, 
                                                    $remaining) 
    {
      switch ($command) {
      case 'x':
      case 'f':
      case 'fe':
        if (substr($remaining, 1, 1) == ' ') {
          $this->extractSubCommand($remaining);
        }
        if ( (mb_strlen($remaining) <= self::MAX_SELAH_CROSS_REFERENCES_LENGTH)
               && (strpos($remaining, ' ') !== False) && ($command = 'x') )
          {
            $this->is_selah_cross_reference = True;
            $this->renderGeneralCommand($command, 1, $remaining);     	
          } else {
          $this->is_selah_cross_reference = False;     
          $this->usfm_text->newFooterEntry();
          //$this->usfm_text->printHtmlTextToFooter($remaining);
          $this->usfm_text->printHtmlText($remaining);
          }
        break;
      case 'x*':
      case 'f*':
      case 'fe*':
          if ($this->is_selah_cross_reference) {
                  $this->renderGeneralCommand($command, 1, $remaining);
          } else {
          $this->usfm_text->closeFooterEntry();
          $this->usfm_text->printHtmlText($remaining);
          }
          break;
      case 'fk':
      case 'xk':
        //$this->usfm_text
        //     ->printHtmlTextToFooter(netscapeCapitalize($remaining));
        $this->usfm_text
             ->printHtmlText(netscapeCapitalize($remaining));  
        break;
      default:
        if (array_key_exists($command, 
                             $this->footnote_substitution_table))
        {
          $setting = $this->footnote_substitution_table[$command];
          $remaining = $setting[self::BEFORE_REMAINING].$remaining.
                       $setting[self::AFTER_REMAINING];
        }
        //$this->usfm_text->printHtmlTextToFooter($remaining);  
        $this->usfm_text->printHtmlText($remaining);  
      }
    }
    
    //406
    protected function renderOther($command, $remaining) {
        switch ($command) {
        case 'nd':
          $this->usfm_text->printHtmlText(netscapeCapitalize($remaining));
          break;
        case 'add': //Yvonne processing add and add* tag here to fix space before punctuation problem
            $this->renderGeneralCommand($command, 1, trim($remaining)); //get rid of space at the end
            break;
        case 'add*': //do not add space if remaining start with punctuation
            if (ctype_punct(substr($remaining, 0, 1))){
                $this->usfm_text->printHtmlText($remaining);
            }else {
                $this->usfm_text->printHtmlText(" ".$remaining);
            }
            
            break;
        default:
          $this->renderGeneralCommand($command, 1, $remaining);
        }
      }
    
    //416
    protected function displayUnsupportedCommand($command, $level,
                                               $remaining)
    {
      if ($level > 1) {
          $command = $command.$level;
      }
          //yil debug
          //$this->usfm_text
          //        ->printHtmlText(" USFMTag alert: Encountered unsupported command:".$command.' '.$remaining."\n");
          $this->usfm_text
           ->printHtmlText("<!-- usfm:\\".$command.' '.$remaining." -->\n");  
    }
    
    //424  
    protected function renderGeneralCommand($command, $level, 
                                          $remaining)
    {  
        
      if (array_key_exists($command, $this->substitution_table)) {   
        $html_command = $this->substitution_table[$command];
        if (sizeof($html_command) > 1) {
          $this->usfm_text
               ->printItalicsToBody($html_command[self::IF_NORMAL],
                                    $html_command[self::IF_ITALIC_PARAGRAPH]);
        } else {
          $this->usfm_text->printHtmlText($html_command[self::IF_NORMAL]);
        }          
        $this->usfm_text->printHtmlText($remaining); 
        
      } elseif (array_key_exists($command, $this->paragraph_settings)) {
                   
        $this->switchParagraph($command, $level);
        $this->usfm_text->printHtmlText($remaining); 
      } elseif (array_key_exists($command, $this->title_settings)) {
        $this->printTitle($command, $level, $remaining);
      } else {
        $this->displayUnsupportedCommand($command, $level, $remaining);
      }
    }
  
    //447
    private function extractSubCommand(&$remaining) {
  	$second_whitespace = strpos($remaining, ' ');
        if ($second_whitespace === False) {
          $second_whitespace = strlen($remaining);
        }
        $result = substr($remaining, 0, $second_whitespace);
        $remaining = substr($remaining, $second_whitespace+1);
        return $result;
      }
      
     
     
    //459  
    private function switchParagraph($command, $level) {
        
        $setting = $this->paragraph_settings[$command];
        $this->usfm_text
             ->switchParagraph($level + $setting[self::BASE_LEVEL] - 1,
                               $setting[self::IS_ITALIC],
                               $setting[self::ALIGNMENT],
                               $setting[self::PARAGRAPH_CLASS]);
      }
      
     //468
     private function printTitle($command, $level, $content) {
        $setting = $this->title_settings[$command];
        $this->usfm_text
             ->printTitle($level + $setting[self::BASE_LEVEL] - 1,
                          $setting[self::IS_ITALIC], $content);

      }  
}

//475  
function netscapeCapitalize($raw_text) {
    // Uppercase all letters, but make the first letter of every word bigger than
    // the rest, i.e. style of headings in the original Netscape Navigator website
    $words = explode(' ', strtoupper($raw_text));
    //wfDebug(sizeof($words));
    for ($i=0; $i<sizeof($words); $i++) {
      if (mb_strlen($words[$i]) > 1) {
        $words[$i] = mb_substr($words[$i], 0, 1)."<small>".
                     mb_substr($words[$i], 1)."</small>";
      }
      //wfDebug($words[$i]);
    }
    return implode(' ', $words);
  }  
    
    


   
?>
