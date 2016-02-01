<?php
/**
 * Plugin door43pagequery : Displays nicely a list of selected pages in one or more namespaces
 *
 * Extends the nspages plugin
 *
 * Author: Phil Hopper
 * Date:   2015-12-12
 */
if(!defined('DOKU_INC')) die();


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_door43pagequery extends syntax_plugin_nspages {

    function connectTo($aMode) {
        $this->Lexer->addSpecialPattern('\{\{door43pages(?m).*?(?-m)\}\}', $aMode, 'plugin_door43pagequery');
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        $return = $this->_getDefaultOptions();
        $return['pos'] = $pos;

        $match = utf8_substr($match, 13, -2); //13 = strlen('{{door43pages')
        $match .= ' ';

        optionParser::checkOption($match, "subns", $return['subns'], true);
        optionParser::checkOption($match, "nopages", $return['nopages'], true);
        optionParser::checkOption($match, "simpleListe?", $return['simpleList'], true);
        optionParser::checkOption($match, "numberedListe?", $return['numberedList'], true);
        optionParser::checkOption($match, "simpleLineBreak", $return['lineBreak'], true);
        optionParser::checkOption($match, "title", $return['title'], true);
        optionParser::checkOption($match, "idAndTitle", $return['idAndTitle'], true);
        optionParser::checkOption($match, "h1", $return['title'], true);
        optionParser::checkOption($match, "simpleLine", $return['simpleLine'], true);
        optionParser::checkOption($match, "sort(By)?Id", $return['sortid'], true);
        optionParser::checkOption($match, "reverse", $return['reverse'], true);
        optionParser::checkOption($match, "pagesinns", $return['pagesinns'], true);
        optionParser::checkOption($match, "nat(ural)?Order", $return['natOrder'], true);
        optionParser::checkOption($match, "sort(By)?Date", $return['sortDate'], true);
        optionParser::checkOption($match, "hidenopages", $return['hidenopages'], true);
        optionParser::checkOption($match, "hidenosubns", $return['hidenosubns'], true);
        optionParser::checkRecurse($match, $return['maxDepth']);
        optionParser::checkNbColumns($match, $return['nbCol']);
        optionParser::checkTextPages($match, $return['textPages'], $this);
        optionParser::checkTextNs($match, $return['textNS'], $this);
        optionParser::checkRegEx($match, "pregPages?On=\"([^\"]*)\"", $return['pregPagesOn']);
        optionParser::checkRegEx($match, "pregPages?Off=\"([^\"]*)\"", $return['pregPagesOff']);
        optionParser::checkRegEx($match, "pregNSOn=\"([^\"]*)\"", $return['pregNSOn']);
        optionParser::checkRegEx($match, "pregNSOff=\"([^\"]*)\"", $return['pregNSOff']);
        optionParser::checkNbItemsMax($match, $return['nbItemsMax']);
        optionParser::checkExclude($match, $return['excludedPages'], $return['excludedNS'], $return['useLegacySyntax']);
        optionParser::checkAnchorName($match, $return['anchorName']);
        optionParser::checkActualTitle($match, $return['actualTitleLevel']);
        optionParser::checkRegEx($match, "q=\"([^\"]*)\"", $return['query']);
        optionParser::checkOption($match, "showcount", $return['showcount'], true);
        optionParser::checkRegEx($match, "fontsize=\"([^\"]*)\"", $return['fontsize']);

        //Now, only the wanted namespaces remains in $match

        // this is for compatibility with some pagequery syntax
        $match = str_replace('@', '', trim($match));
        $matches = array_filter(explode(' ', $match));

        // get the namespaces to search
        foreach ($matches as $ns) {

            $nsFinder = new namespaceFinder($ns);
            if ($nsFinder->isNsSafe()) {
                $return['requested_namespaces'][] = $nsFinder->getWantedNs();
                $return['requested_directories'][] = $nsFinder->getWantedDirectory();
            }
        }

        return $return;
    }

    public function _getDefaultOptions(){
        return array(
            'subns'         => false, 'nopages' => false, 'simpleList' => false, 'lineBreak' => false,
            'excludedPages' => array(), 'excludedNS' => array(),
            'title'         => false, 'wantedNS' => '', 'wantedDir' => '', 'safe' => true,
            'textNS'        => '', 'textPages' => '', 'pregPagesOn' => array(),
            'pregPagesOff'  => array(), 'pregNSOn' => array(), 'pregNSOff' => array(),
            'maxDepth'      => (int) 1, 'nbCol' => 3, 'simpleLine' => false,
            'sortid'        => false, 'reverse' => false,
            'pagesinns'     => false, 'anchorName' => null, 'actualTitleLevel' => false,
            'idAndTitle'    => false, 'nbItemsMax' => 0, 'numberedList' => false,
            'natOrder'      => false, 'sortDate' => false, 'useLegacySyntax' => false,
            'hidenopages'   => false, 'hidenosubns' => false,
            'requested_namespaces'  => array(),
            'requested_directories' => array(),
            'showcount' => false,
            'fontsize' => array()
        );
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        global $door43shared;

        if ($mode != 'xhtml') return false;

        /* @var $door43shared helper_plugin_door43shared */
        if (empty($door43shared)) {
            $door43shared = plugin_load('helper', 'door43shared');
        }

        $this->_deactivateTheCacheIfNeeded($renderer);

        if ( $data['useLegacySyntax'] ){
            action_plugin_nspages::logUseLegacySyntax();
        }

        $uuid = $door43shared->getGUID(false);
        $data['div_id'] = $uuid;
        $json = json_encode($data);
        $span = '<span class="waiting">' . $this->getLang('waiting') . '</span>';

        // this is split in order to keep PhpStorm from trying to parse it as javascript
        $script = '<script' . " type=\"text/javascript\">door43pagequery.push({$json});</script>";

        $renderer->doc .= "<div class=\"door43pagequery plugin_nspages\" id=\"{$uuid}\" >{$span}{$script}</div>";
        return true;
    }

    private function _deactivateTheCacheIfNeeded(Doku_Renderer $renderer) {
        if ($this->getConf('cache') == 1){
            $renderer->nocache(); //disable cache
        }
    }
}
