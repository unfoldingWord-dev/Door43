Dokuwiki-USFMTag
=================

A text filter that converts USFM Scripture Text to HTML in Dokuwiki. Originally developed for MediaWiki by Rusmin 
Soetjipto.

Ported to Dokuwiki by Yvonne Lu (yvonne@leapinglaptop.com)

version 1.1 1/13/14
 ported function renderOther, renderTable, renderIntroduction to support command
 'i', 'it', 'd', 'r', 't', 'tl','x'

version 1.0 10/29/13
- added syntax.php
- added action.php to open .usfm text files directly added to the data/pages directory.
    Note:  the file must end in .txt or Dokuwiki will not see it
- The stand along .usfm text files are not expected to have <USFM></UFSM> tags.
    action.php will insert these begin and end tags for the file.
- If insert usfm syntaxed text in an existing page, one must begin with <USFM> tag and end
    the text using </USFM> tag
- command not yet ported:
    'i', 'it', 'd', 'r', 't', 'tl', 'x' and all other commands
    I'm looking for test files that will help me test porting these commands.  
    If you have one, please email me about it.

- commands ported:
    'h', 'id', 'rem', 'sts', 'toc', 'm', 'mi', 's', 'sc', 'sig', 'sls', 'c', 'v', 'q', 'qt',
    'p', 'pb', 'pn', 'pro', 'b', 'cls', 'li', 'nb', 'f', 'fig'




