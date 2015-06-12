<?php
/**
 * Ditaa-Plugin: Converts Ascii-Flowcharts into a png-File
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Dennis Ploeger <develop [at] dieploegers [dot] de>
 * @author      Christoph Mertins <c [dot] mertins [at] gmail [dot] com>
 * @author      Andreas Gohr <andi@splitbrain.org>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_ditaa extends DokuWiki_Syntax_Plugin {

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'normal';
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 200;
    }

    /**
     * Connect pattern to lexer
     */

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<ditaa.*?>\n.*?\n</ditaa>',$mode,'plugin_ditaa');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        $info = $this->getInfo();

        // prepare default data
        $return = array(
                        'width'     => 0,
                        'height'    => 0,
                        'antialias' => true,
                        'edgesep'   => true,
                        'round'     => false,
                        'shadow'    => true,
                        'scale'     => 1,
                        'align'     => '',
                        'version'   => $info['date'], //forece rebuild of images on update
                       );


        // prepare input
        $lines = explode("\n",$match);
        $conf = array_shift($lines);
        array_pop($lines);

        // match config options
        if(preg_match('/\b(left|center|right)\b/i',$conf,$match)) $return['align'] = $match[1];
        if(preg_match('/\b(\d+)x(\d+)\b/',$conf,$match)){
            $return['width']  = $match[1];
            $return['height'] = $match[2];
        }
        if(preg_match('/\b(\d+(\.\d+)?)X\b/',$conf,$match)) $return['scale']  = $match[1];
        if(preg_match('/\bwidth=([0-9]+)\b/i', $conf,$match)) $return['width'] = $match[1];
        if(preg_match('/\bheight=([0-9]+)\b/i', $conf,$match)) $return['height'] = $match[1];
        // match boolean toggles
        if(preg_match_all('/\b(no)?(antialias|edgesep|round|shadow)\b/i',$conf,$matches,PREG_SET_ORDER)){
            foreach($matches as $match){
                $return[$match[2]] = ! $match[1];
            }
        }

        $input = join("\n",$lines);
        $return['md5'] = md5($input); // we only pass a hash around

        // store input for later use
        io_saveFile($this->_cachename($return,'txt'),$input);

        return $return;
    }

    /**
     * Cache file is based on parameters that influence the result image
     */
    function _cachename($data,$ext){
        unset($data['width']);
        unset($data['height']);
        unset($data['align']);
        return getcachename(join('x',array_values($data)),'.ditaa.'.$ext);
    }

    /**
     * Create output
     */
    function render($format, &$R, $data) {
        if($format == 'xhtml'){
            $img = DOKU_BASE.'lib/plugins/ditaa/img.php?'.buildURLparams($data);
            $R->doc .= '<img src="'.$img.'" class="media'.$data['align'].'" alt=""';
            if($data['width'])  $R->doc .= ' width="'.$data['width'].'"';
            if($data['height']) $R->doc .= ' height="'.$data['height'].'"';
            if($data['align'] == 'right') $R->doc .= ' align="right"';
            if($data['align'] == 'left')  $R->doc .= ' align="left"';
            $R->doc .= '/>';
            return true;
        }elseif($format == 'odt'){
            $src = $this->_imgfile($data);
            $R->_odtAddImage($src,$data['width'],$data['height'],$data['align']);
            return true;
        }
        return false;
    }


    /**
     * Return path to the rendered image on our local system
     */
    function _imgfile($data){
        $cache  = $this->_cachename($data,'png');

        // create the file if needed
        if(!file_exists($cache)){
            $in = $this->_cachename($data,'txt');
            if($this->getConf('java')){
                $ok = $this->_run($data,$in,$cache);
            }else{
                $ok = $this->_remote($data,$in,$cache);
            }
            if(!$ok) return false;
            clearstatcache();
        }

        // resized version
        if($data['width']){
            $cache = media_resize_image($cache,'png',$data['width'],$data['height']);
        }

        // something went wrong, we're missing the file
        if(!file_exists($cache)) return false;

        return $cache;
    }

    /**
     * Render the output remotely at ditaa.org
     */
    function _remote($data,$in,$out){
        if(!file_exists($in)){
            if($conf['debug']){
                dbglog($in,'no such ditaa input file');
            }
            return false;
        }

        $http = new DokuHTTPClient();
        $http->timeout=30;

        $pass = array();
        $pass['scale']   = $data['scale'];
        $pass['timeout'] = 25;
        $pass['grid']    = io_readFile($in);
        if(!$data['antialias']) $pass['A'] = 'on';
        if(!$data['shadow'])    $pass['S'] = 'on';
        if($data['round'])      $pass['r'] = 'on';
        if(!$data['edgesep'])   $pass['E'] = 'on';

        $img = $http->post('http://ditaa.org/ditaa/render',$pass);
        if(!$img) return false;

        return io_saveFile($out,$img);
    }

    /**
     * Run the ditaa Java program
     */
    function _run($data,$in,$out) {
        global $conf;

        if(!file_exists($in)){
            if($conf['debug']){
                dbglog($in,'no such ditaa input file');
            }
            return false;
        }

        $cmd  = $this->getConf('java');
        $cmd .= ' -Djava.awt.headless=true -Dfile.encoding=UTF-8 -jar';
        $cmd .= ' '.escapeshellarg(dirname(__FILE__).'/ditaa/ditaa0_9.jar'); //ditaa jar
        $cmd .= ' --encoding UTF-8';
        $cmd .= ' '.escapeshellarg($in); //input
        $cmd .= ' '.escapeshellarg($out); //output
        $cmd .= ' -s '.escapeshellarg($data['scale']);
        if(!$data['antialias']) $cmd .= ' -A';
        if(!$data['shadow'])    $cmd .= ' -S';
        if($data['round'])      $cmd .= ' -r';
        if(!$data['edgesep'])   $cmd .= ' -E';

        exec($cmd, $output, $error);

        if ($error != 0){
            if($conf['debug']){
                dbglog(join("\n",$output),'ditaa command failed: '.$cmd);
            }
            return false;
        }

        return true;
    }

}

