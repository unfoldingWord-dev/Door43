<?php
/**
 * DokuWiki Action Plugin GoogleFonts
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Tamara Phillips <tamara.phillips@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_googlefonts extends DokuWiki_Action_Plugin {

    // register hook
    function register(&$controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT','BEFORE', $this, '_addFontCode');
    }

    /**
     * @param unknown_type $event
     * @param unknown_type $param
     */
    function _addFontCode(&$event, $param) {

        $CSSfiles = array();
        $CSSembed = array();
		$fontNames = array();

		for ($i = 1; $i <= 6; $i++) {
			${fontName.$i} = $this->getConf('fontName'.$i);
			${headings.$i} = $this->getConf('headings'.$i);
			${genFamily.$i} = $this->getConf('genFamily'.$i);
			${addStyle.$i} = $this->getConf('addStyle'.$i);
			$fontNames[] = ${fontName.$i};
	        // add styles
		    // if not set, set them through CSS as usual
	        if ( ${addStyle.$i} && !empty(${fontName.$i}) ) {
		        $CSSembed[] = ${headings.$i}." { font-family: '".preg_replace('/:.*/','',${fontName.$i})."', ".${genFamily.$i}."; }";
			}
		}

        $CSSfiles = array(
			'//fonts.googleapis.com/css?family='.trim(implode("|",str_replace(' ', '+', $fontNames)),"|")
		);

        // include all relevant CSS files
        if (!empty($CSSfiles)) {
            foreach($CSSfiles as $CSSfile) {
                $event->data['link'][] = array(
                    'type'    => 'text/css',
                    'rel'     => 'stylesheet',
                    'media'   => 'screen',
                    'href'    => $CSSfile
                );
            }
        }
        // embed all relevant CSS code
        if (!empty($CSSembed)){
			foreach($CSSembed as $CSSembeded) {
	            $event->data['style'][] = array(
		            'type'    => 'text/css',
			        'media'   => 'screen',
				    '_data'   => $CSSembeded
	            );
			}
        }
    }
}
