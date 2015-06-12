<?php
/**
 * Copyright (c) 2011 Rusmin Soetjipto
 * ported to Dokuwiki by Yvonne Lu
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

class UsfmParagraphState {
  
  private $list_level = 0;
  private $indent_level = 0;  // 0 for normal (level-1) paragraph
                              // 1 for level-1 poetry & indented
                              //   paragraph
  private $is_italic = False;
  private $paragraph_class = '';
  private $is_open = False;

   
function switchListLevel($new_list_level) {
    $result = '';
    for ($r = $new_list_level; $r < $this->list_level; $r++) {
      $result .= "\n</ul>";
    }
    for ($r = $this->list_level; $r < $new_list_level; $r++) {
      $result .= "\n<ul class='usfm'>";
    }
    $this->list_level = $new_list_level;
    return $result;
  }  
  
  
  function closeParagraph() {
    if ($this->is_open) {
      $result .= $this->switchListLevel(0); 
      if ($this->is_italic) {
        $result .= '</i>';
        $this->is_italic = False;
      }  
      $result .= "</p>\n";
      $this->is_open = False;
      return $result;
    } else {
      return '';
    }
  }  
  
 
  private function switchIndentLevel($new_indent_level) {
    $result = $this->closeParagraph();
    
    for ($r = $new_indent_level; $r < $this->indent_level; $r++) {
      $result .= "</blockquote>\n";
    }
    for ($r = $this->indent_level; $r < $new_indent_level; $r++) {
      $result .= "<blockquote class='usfm'>\n";
    }
    $this->indent_level = $new_indent_level;
    return $result;
  }
  
  
 function switchParagraph($new_indent_level, $is_italic, $alignment, 
                           $paragraph_class)
  {
    $result = $this->switchIndentLevel($new_indent_level);
    $result .= "<p class='".$paragraph_class."' align='".$alignment."'>";
    if ($is_italic) {
      $this->is_italic = True;
      $result .= '<i>';
    }
    $this->paragraph_class = $paragraph_class;
    $this->is_open = True;
    return $result;
  }
  
 function printTitle($is_horizontal_line, $level, $is_italic,
                      $content) {
    $result = $this->switchIndentLevel(0);

    if ($is_horizontal_line) {
      $result .= "<hr>";
    }
    if ($level > 6) {
      $level = 6;
    }
    if ($is_italic) {
      $result .= "<h".$level." class='usfm'><i>".$content."</i></h".$level.">";
    } else {
      $result .= "<h".$level." class='usfm'>".$content."</h".$level.">";
    }
    return $result;
  }
  
  //111
  function isItalic() {
    return $this->is_italic;
  }
  
  //115
  function isOpen() {
  	return $this->is_open;
  }
  
  //119
  function getParagraphClass() {
  	return $this->paragraph_class;
  }
}
?>