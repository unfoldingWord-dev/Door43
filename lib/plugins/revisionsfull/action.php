<?php
/**
 * 
 *
 * @author     David Stone
 */
if(!defined('DOKU_INC')) die();

if(!class_exists('PageChangeLog')) require 'changelog.php';
 
 
class action_plugin_revisionsfull extends DokuWiki_Action_Plugin {
 
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    public function register(Doku_Event_Handler $controller) {
        
        $controller->register_hook(
            'TPL_ACT_RENDER',
            'BEFORE',
            $this,
            '_handle_before'
        );
    }
 
    /**
     * Hook js script into page headers.
     *
     * @author Samuele Tognini <samuele@cli.di.unipi.it>
     */
    public function _handle_before(Doku_Event $event, $param) {
        $act = act_clean($event->data);
        
        if($act != 'diff') {
            return;
        } 
        $event->preventDefault();
        
        revisionsfull_html_diff();
    }
}


/**
 *  Wikipedia Table style diff formatter.
 *
 */
class FullTableDiffFormatter extends TableDiffFormatter {
    

    function __construct() {
        $this->leading_context_lines = PHP_INT_MAX; // print out max context will be all of it. 
        $this->trailing_context_lines = PHP_INT_MAX;
    }
    
    function _block_header($xbeg, $xlen, $ybeg, $ylen) {
        return '<tr><td>&nbsp;</td></tr>';
    }
}

class RenderedDiffFormatter extends TableDiffFormatter {

    function __construct($rev1, $rev2, $rev3) {
        $this->leading_context_lines = PHP_INT_MAX; // print out max context will be all of it. 
        $this->trailing_context_lines = PHP_INT_MAX;
        $this->rev1 = $rev1;
        $this->rev2 = $rev2;
        $this->rev3 = $rev3;
    }
    
    function _block_header($xbeg, $xlen, $ybeg, $ylen) {
        return '<tr><td>&nbsp;</td></tr>';
    }

    function _block($xbeg, $xlen, $ybeg, $ylen, &$edits) {
        global $ID;
        $this->_start_block($this->_block_header($xbeg, $xlen, $ybeg, $ylen));
        echo "<tr>";
        echo "<td colspan=".$this->colspan."><div id=\"diff-div-1\" style=\"overflow-y: scroll; height: 50em;\">".p_wiki_xhtml($ID, $rev=$this->rev1)."</div></td>";
        echo "<td colspan=".$this->colspan."><div id=\"diff-div-2\" style=\"overflow-y: scroll; height: 50em;\">".p_wiki_xhtml($ID, $rev=$this->rev2)."</div></td>";
        if (is_null($this->rev3) == false) {
            echo "<td colspan=".$this->colspan."><div id=\"diff-div-3\" style=\"overflow-y: scroll; height: 50em;\">".p_wiki_xhtml($ID, $rev=$this->rev3)."</div></td>";
        }
        echo "</tr>";
        $this->_end_block();
    }
}


if(!function_exists('html_diff_navigation')) {
    /**
 * Create html for revision navigation
 *
 * @param PageChangeLog $pagelog changelog object of current page
 * @param string        $type    inline vs sidebyside
 * @param int           $l_rev   left revision timestamp
 * @param int           $r_rev   right revision timestamp
 * @return string[] html of left and right navigation elements
 */
function html_diff_navigation($pagelog, $type, $l_rev, $r_rev) {
    global $INFO, $ID;
    // last timestamp is not in changelog, retrieve timestamp from metadata
    // note: when page is removed, the metadata timestamp is zero
    if(!$r_rev) {
        if(isset($INFO['meta']['last_change']['date'])) {
            $r_rev = $INFO['meta']['last_change']['date'];
        } else {
            $r_rev = 0;
        }
    }
    //retrieve revisions with additional info
    list($l_revs, $r_revs) = $pagelog->getRevisionsAround($l_rev, $r_rev);
    $l_revisions = array();
    if(!$l_rev) {
        $l_revisions[0] = array(0, "", false); //no left revision given, add dummy
    }
    foreach($l_revs as $rev) {
        $info = $pagelog->getRevisionInfo($rev);
        $l_revisions[$rev] = array(
            $rev,
            dformat($info['date']) . ' ' . editorinfo($info['user'], true) . ' ' . $info['sum'],
            $r_rev ? $rev >= $r_rev : false //disable?
        );
    }
    $r_revisions = array();
    if(!$r_rev) {
        $r_revisions[0] = array(0, "", false); //no right revision given, add dummy
    }
    foreach($r_revs as $rev) {
        $info = $pagelog->getRevisionInfo($rev);
        $r_revisions[$rev] = array(
            $rev,
            dformat($info['date']) . ' ' . editorinfo($info['user'], true) . ' ' . $info['sum'],
            $rev <= $l_rev //disable?
        );
    }
    //determine previous/next revisions
    $l_index = array_search($l_rev, $l_revs);
    $l_prev = $l_revs[$l_index + 1];
    $l_next = $l_revs[$l_index - 1];
    if($r_rev) {
        $r_index = array_search($r_rev, $r_revs);
        $r_prev = $r_revs[$r_index + 1];
        $r_next = $r_revs[$r_index - 1];
    } else {
        //removed page
        if($l_next) {
            $r_prev = $r_revs[0];
        } else {
            $r_prev = null;
        }
        $r_next = null;
    }
    /*
     * Left side:
     */
    $l_nav = '';
    //move back
    if($l_prev) {
        $l_nav .= html_diff_navigationlink($type, 'diffbothprevrev', $l_prev, $r_prev);
        $l_nav .= html_diff_navigationlink($type, 'diffprevrev', $l_prev, $r_rev);
    }
    //dropdown
    $form = new Doku_Form(array('action' => wl()));
    $form->addHidden('id', $ID);
    $form->addHidden('difftype', $type);
    $form->addHidden('rev2[1]', $r_rev);
    $form->addHidden('do', 'diff');
    $form->addElement(
         form_makeListboxField(
             'rev2[0]',
             $l_revisions,
             $l_rev,
             '', '', '',
             array('class' => 'quickselect')
         )
    );
    $form->addElement(form_makeButton('submit', 'diff', 'Go'));
    $l_nav .= $form->getForm();
    //move forward
    if($l_next && ($l_next < $r_rev || !$r_rev)) {
        $l_nav .= html_diff_navigationlink($type, 'diffnextrev', $l_next, $r_rev);
    }
    /*
     * Right side:
     */
    $r_nav = '';
    //move back
    if($l_rev < $r_prev) {
        $r_nav .= html_diff_navigationlink($type, 'diffprevrev', $l_rev, $r_prev);
    }
    //dropdown
    $form = new Doku_Form(array('action' => wl()));
    $form->addHidden('id', $ID);
    $form->addHidden('rev2[0]', $l_rev);
    $form->addHidden('difftype', $type);
    $form->addHidden('do', 'diff');
    $form->addElement(
         form_makeListboxField(
             'rev2[1]',
             $r_revisions,
             $r_rev,
             '', '', '',
             array('class' => 'quickselect')
         )
    );
    $form->addElement(form_makeButton('submit', 'diff', 'Go'));
    $r_nav .= $form->getForm();
    //move forward
    if($r_next) {
        if($pagelog->isCurrentRevision($r_next)) {
            $r_nav .= html_diff_navigationlink($type, 'difflastrev', $l_rev); //last revision is diff with current page
        } else {
            $r_nav .= html_diff_navigationlink($type, 'diffnextrev', $l_rev, $r_next);
        }
        $r_nav .= html_diff_navigationlink($type, 'diffbothnextrev', $l_next, $r_next);
    }

    return array($l_nav, $r_nav);
}
}


if(!function_exists('html_diff_navigationlink')) {
    /**
 * Create html link to a diff defined by two revisions
 *
 * @param string $difftype display type
 * @param string $linktype
 * @param int $lrev oldest revision
 * @param int $rrev newest revision or null for diff with current revision
 * @return string html of link to a diff
 */
function html_diff_navigationlink($difftype, $linktype, $lrev, $rrev = null) {
    global $ID, $lang;
    if(!$rrev) {
        $urlparam = array(
            'do' => 'diff',
            'rev' => $lrev,
            'difftype' => $difftype,
        );
    } else {
        $urlparam = array(
            'do' => 'diff',
            'rev2[0]' => $lrev,
            'rev2[1]' => $rrev,
            'difftype' => $difftype,
        );
    }
    return  '<a class="' . $linktype . '" href="' . wl($ID, $urlparam) . '" title="' . $lang[$linktype] . '">' .
                '<span>' . $lang[$linktype] . '</span>' .
            '</a>' . "\n";
}
}

/**
 * 
 * Show diff
 * between current page version and provided $text
 * or between the revisions provided via GET or POST
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @param  string $text  when non-empty: compare with this text with most current version
 * @param  bool   $intro display the intro text
 * @param  string $type  type of the diff (inline or sidebyside)
 */
function revisionsfull_html_diff($text = '', $intro = true, $type = null) {

    // error_log("----- revisionsfull_html_diff($text, $intro, $type)");

    global $ID;
    global $REV;
    global $lang;
    global $INPUT;
    global $INFO;
    $pagelog = new PageChangeLog($ID);
    /*
     * Determine diff type
     */
    if(!$type) {
        $type = $INPUT->str('difftype');
        if(empty($type)) {
            $type = get_doku_pref('difftype', $type);
            if(empty($type) && $INFO['ismobile']) {
                $type = 'inline';
            }
        }
    }
    if(!in_array($type, array('inline', 'sidebyside', 'rendered'))) $type = 'full';
    /*
     * Determine requested revision(s)
     */
    // we're trying to be clever here, revisions to compare can be either
    // given as rev and rev2 parameters, with rev2 being optional. Or in an
    // array in rev2.
    $rev1 = $REV;
    $rev2 = $INPUT->ref('rev2');
    $rev3 = null;
    if(is_array($rev2)) {
        $rev1 = (int) $rev2[0];
        if (array_key_exists(2, $rev2)) {
            $rev3 = (int) $rev2[2];
            if ($rev3==0) {
                $rev3="";
            }
        }
        $rev2 = (int) $rev2[1];
        if(!$rev1) {
            $rev1 = $rev2;
            unset($rev2);
        }
    } else {
        $rev2 = $INPUT->int('rev2');
    }
    // error_log("rev1: $rev1");
    // error_log("rev2: $rev2");
    // error_log("rev3: $rev3");
    /*
     * Determine left and right revision, its texts and the header
     */
    $r_minor = '';
    $l_minor = '';
    if($text) { // compare text to the most current revision
        $l_rev = '';
        $l_text = rawWiki($ID, '');
        $l_head = '<a class="wikilink1" href="' . wl($ID) . '">' .
            $ID . ' ' . dformat((int) @filemtime(wikiFN($ID))) . '</a> ' .
            $lang['current'];
        $r_rev = '';
        $r_text = cleanText($text);
        $r_head = $lang['yours'];
    } else {
        if($rev1 && isset($rev2) && $rev2) { // two specific revisions wanted
            // make sure order is correct (older on the left)
            if($rev1 < $rev2) {
                $l_rev = $rev1;
                $r_rev = $rev2;
            } else {
                $l_rev = $rev2;
                $r_rev = $rev1;
            }
        } elseif($rev1) { // single revision given, compare to current
            $r_rev = '';
            $l_rev = $rev1;
        } else { // no revision was given, compare previous to current
            $r_rev = '';
            $revs = $pagelog->getRevisions(0, 1);
            $l_rev = $revs[0];
            $REV = $l_rev; // store revision back in $REV
        }
        // when both revisions are empty then the page was created just now
        if(!$l_rev && !$r_rev) {
            $l_text = '';
        } else {
            $l_text = rawWiki($ID, $l_rev);
        }
        $r_text = rawWiki($ID, $r_rev);
        list($l_head, $r_head, $l_minor, $r_minor) = html_diff_head($l_rev, $r_rev, null, false, $type == 'inline');
    }
    /*
     * Build navigation
     */
    $l_nav = '';
    $r_nav = '';
    if(!$text) {
        list($l_nav, $r_nav) = html_diff_navigation($pagelog, $type, $l_rev, $r_rev);
    }

    $t_nav = "";
    //dropdown
    $t_revisions = array();
    list($r_revs, $t_revs) = $pagelog->getRevisionsAround($r_rev, $rev3);
    foreach($t_revs as $rev) {
        $info = $pagelog->getRevisionInfo($rev);
        $t_revisions[$rev] = array(
            $rev,
            dformat($info['date']) . ' ' . editorinfo($info['user'], true) . ' ' . $info['sum'],
            $rev <= $r_rev //disable?
        );
    }
    $form = new Doku_Form(array('action' => wl()));
    $form->addHidden('id', $ID);
    $form->addHidden('rev2[0]', $l_rev);
    $form->addHidden('rev2[1]', $r_rev);
    $form->addHidden('difftype', $type);
    $form->addHidden('do', 'diff');
    $form->addElement(
         form_makeListboxField(
             'rev2[2]',
             $t_revisions,
             $rev3,
             '', '', '',
             array('class' => 'quickselect')
         )
    );
    $form->addElement(form_makeButton('submit', 'diff', 'Go'));
    $t_nav .= $form->getForm();
    $t_minor = "";
    $info = $pagelog->getRevisionInfo($rev3);
    if ($info['type']===DOKU_CHANGE_TYPE_MINOR_EDIT) {
        $t_minor = 'class="minor"';
    }
    $t_user = '<bdi>'.editorinfo($info['user']).'</bdi>';
    $t_sum   = ($info['sum']) ? '<span class="sum"><bdi>'.hsc($info['sum']).'</bdi></span>' : '';
    $t_head_title = $ID.' ['.dformat($rev3).']';
    $t_head = '<bdi><a class="wikilink1" href="'.wl($ID,"rev=$rev3").'">'. $t_head_title.'</a></bdi><br/>'.$t_user.' '.$t_sum;

    /*
     * Create diff object and the formatter
     */
    $diff = new Diff(explode("\n", $l_text), explode("\n", $r_text));
    if($type == 'inline') {
        $diffformatter = new InlineDiffFormatter();
    } elseif($type == 'sidebyside') {
        $diffformatter = new TableDiffFormatter();
    } elseif($type == 'rendered') {
        $diffformatter = new RenderedDiffFormatter($rev1, $rev2, $rev3);
    } else {
        $diffformatter = new FullTableDiffFormatter();
    }
    /*
     * Display intro
     */
    if($intro) print p_locale_xhtml('diff');
    /*
     * Display type and exact reference
     */
    if(!$text) {
        ptln('<div class="diffoptions group">');
        $form = new Doku_Form(array('action' => wl()));
        $form->addHidden('id', $ID);
        $form->addHidden('rev2[0]', $l_rev);
        $form->addHidden('rev2[1]', $r_rev);
        $form->addHidden('do', 'diff');
        $form->addElement(
             form_makeListboxField(
                 'difftype',
                 array(
                     'full' => 'Full Side by Side',
                     'sidebyside' => $lang['diff_side'],
                     'inline' => $lang['diff_inline'],
                     'rendered' => 'Rendered'
                 ),
                 $type,
                 $lang['diff_type'],
                 '', '',
                 array('class' => 'quickselect')
             )
        );
        $form->addElement(form_makeButton('submit', 'diff', 'Go'));
        $form->printForm();
        ptln('<p>');
        // link to exactly this view FS#2835
        echo html_diff_navigationlink($type, 'difflink', $l_rev, $r_rev ? $r_rev : $INFO['currentrev']);
        ptln('</p>');
        ptln('</div>'); // .diffoptions
    }
    /*
     * Display diff view table
     */
    ?>
    <div class="table">
    <table class="diff diff_<?php echo $type ?>">

        <?php
        if ($type="rendered" and is_null($rev3) == false) {
            ?>
            <tr>
                <td colspan="99" align="right">
                <input type="checkbox" id="lock_scrollbars" checked />Lock scrollbars
                </td>
            </tr>
            <?php
        }
        //navigation and header
        if($type == 'inline') {
            if(!$text) { ?>
                <tr>
                    <td class="diff-lineheader">-</td>
                    <td class="diffnav"><?php echo $l_nav ?></td>
                </tr>
                <tr>
                    <th class="diff-lineheader">-</th>
                    <th <?php echo $l_minor ?>>
                        <?php echo $l_head ?>
                    </th>
                </tr>
            <?php } ?>
            <tr>
                <td class="diff-lineheader">+</td>
                <td class="diffnav"><?php echo $r_nav ?></td>
            </tr>
            <tr>
                <th class="diff-lineheader">+</th>
                <th <?php echo $r_minor ?>>
                    <?php echo $r_head ?>
                </th>
            </tr>
        <?php } else {
            if(!$text) { ?>
                <tr>
                    <?php if (is_null($rev3) == false) { ?>
                        <td colspan="2" class="diffnav" style="width: 33%"><?php echo $l_nav ?></td>
                        <td colspan="2" class="diffnav" style="width: 33%"><?php echo $r_nav ?></td>
                        <td colspan="2" class="diffnav" style="width: 33%"><?php echo $t_nav ?></td>
                    <?php } else { ?>
                        <td colspan="2" class="diffnav" style="width: 50%"><?php echo $l_nav ?></td>
                        <td colspan="2" class="diffnav" style="width: 50%"><?php echo $r_nav ?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
            <tr>
                <th colspan="2" <?php echo $l_minor ?>>
                    <?php echo $l_head ?>
                </th>
                <th colspan="2" <?php echo $r_minor ?>>
                    <?php echo $r_head ?>
                </th>
                <?php if (is_null($rev3) == false) { ?>
                    <th colspan="2" <?php echo $t_minor ?>>
                        <?php echo $t_head ?>
                    </th>
                <?php } ?>
            </tr>
        <?php }
        //diff view
        echo html_insert_softbreaks($diffformatter->format($diff)); ?>

    </table>
    </div>

    <?php if ($type=="rendered" and is_null($rev3) == false) { ?>
    <script>
    jQuery(document).ready(function ($) {
        /** Locks scrollbars such that a scroll event on `source`
         *  will cause `dest1` and `dest2` to scroll to the same spot. 
         */
        function lockScrollbars(source, dest1, dest2) {
            $(source).scroll(function() {
                if ($("#lock_scrollbars").is(":checked")) {
                    var position = $(source).scrollTop();
                    $(dest1).scrollTop(position);
                    $(dest2).scrollTop(position);
                }
            });
        }
        lockScrollbars("div#diff-div-1", "div#diff-div-2", "div#diff-div-3");
        lockScrollbars("div#diff-div-2", "div#diff-div-1", "div#diff-div-3");
        lockScrollbars("div#diff-div-3", "div#diff-div-1", "div#diff-div-2");
    });
    </script>
    <?php } ?>
<?php
}
