<?php
/**
 * DokuWiki Plugin door43search (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Gerrit Uitslag <klapinklapin@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class action_plugin_door43search
 */
class action_plugin_door43search extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_search_query_fullpage');
        //$controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'BEFORE', $this, '_search_query_pagelookup');

        //controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_search_query_fullpage');
//ACTION_ACT_PREPROCESS
//TPL_ACT_RENDER
    }

    /**
     * Restrict fullpage search to namespace given as url parameter
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
public function _search_query_fullpage(Doku_Event &$event, $param) {
        //$this->_addNamespace2query($event->data['query']);

//echo ($_GET['startdate']."<BR>");
//echo ($_GET['enddate']."<BR>");
//echo ($_GET['ns_path']."<BR>");

//foreach ($_POST as $key => $value) {
    //do something
    //echo $key . '=>' . $value."<BR>";
//
$get_start_date = $_POST['startdate'];
$get_end_date = $_POST['enddate'];
$get_namespace = $_POST['ns_path'];
$get_namespace = substr($get_namespace,1);

$start_timestamp = 0;
$end_timestamp = 0;

if ($get_namespace =='root'){
	$get_namespace = rtrim($doc_home);
}


if ($get_start_date!=null){
	$start_timestamp = strtotime($get_start_date);
}
if ($get_end_date!=null){
	$end_timestamp = strtotime($get_end_date);
}

if ($get_namespace!=null){
	$get_namespace = str_replace(":","/",$get_namespace);
}

//echo ($_GET['startdate']."<BR>");
//echo ($_GET['enddate']."<BR>");
//echo ($get_namespace."?<BR>");

//echo ($start_timestamp."<BR>");
//echo ($end_timestamp."<BR>");
		if($get_start_date."" !=""){
			echo ("<H2>Result for door43search:</H2>");
			echo ("Created between: [$get_start_date] to [$get_end_date] in: [home/$get_namespace]<BR>");
		}
		$doc_mainlist = fopen("data/index/page.idx", "r");
		$doc_home = fgets($doc_mainlist);
		$doc_home = rtrim($doc_home);
		$doc_home_lenght = strlen($doc_home);
		echo ("<BR>");
		while(!feof($doc_mainlist)){
			$doc_file =  fgets($doc_mainlist);
			
			if(strlen($doc_file) > 1){
				$doc_file = str_replace(":","/",$doc_file);
				$doc_file = rtrim($doc_file);

				//echo($doc_file.":::<BR>");

				$tempstr = "data/meta/".$doc_file.".changes";
				//echo($tempstr."<BR>");

				$tempfile = fopen($tempstr, "r");
				$doc_creation_date = fgets($tempfile);
				//echo ($doc_creation_date);
				//echo date('Y/m/d', $doc_creation_date)."<BR>";

				$first_folder_name = null;
				//echo "<BR>".$doc_file."<BR>";
				if(strpos($doc_file,"/")>0){
					$tempint = strpos($doc_file,"/");
					$first_folder_name = substr($doc_file,0,$tempint);
					//echo $first_folder_name."<BR>";
				}


				$get_namespace = str_replace(":","/",$get_namespace);//[home:beta] -> [home/beta]

				//echo "F:".substr(strrchr($doc_file,"/"),1)."<br>";
				$f_name = substr(strrchr($doc_file,"/"),1);

				//echo "!".$f_name.":<BR>";

				$NS = false;

				//echo "!".$get_namespace.":<BR>";
				//echo "!!".$doc_file.":<BR>";
				

				//if (strpos($doc_file,"/") === false){
				//	echo "False<BR>!!!";
				//}else{
				//	echo "True<BR>!!!";
				//}

				if (($get_namespace == null) && (strpos($doc_file,"/")===false)){
					$NS=true;
					//echo "OK<BR>";
				//} else if (($get_namespace."/".$f_name == $doc_file) && (strpos($doc_file,"/")===false)){
				} else if ($get_namespace."/".$f_name == $doc_file){
					$NS=true;
					//echo "OK1<BR>";
				}else if(( $get_namespace == $doc_home ) && (strpos($doc_file,"/")===false)){//[home]
					//echo("OK2<BR>");
					//if $get_namespace==home and no more / in doc_file
					//$get_namespace = "";
					$NS=true;
					//echo "OK2<BR>";

				}else if( $get_namespace == $doc_home."/"){// [home/]
					//if $get_namespace==home/ and no more / in doc_file
					//$get_namespace = "";
					if ( (strpos($doc_file,"/")===false)){
						$NS=true;
						//echo "OK3<BR>";

					}
				//}else if( $get_namespace == $doc_home."/".$first_folder_name ){//home + "/" + bate;
				}else if( $get_namespace == $doc_home ){//home + "/" + bate;
					$doc_home = substr($doc_home,$doc_home_lenght + 1);
					if (strpos($doc_file,$doc_home)==0){
						$NS=true;
						//echo "OK4<BR>";

					}
				}

				IF($NS){
					if(($start_timestamp <= $doc_creation_date)&&($end_timestamp >= $doc_creation_date)){
						echo "<A HREF='".$doc_file."'>".$doc_file."</A><BR>";
					}
				}
				
			}
		}


		echo ("<BR>");
		echo ("<BR>");

        //$event->stopPropagation();
        //$event->preventDefault();
    }

    /**
     * Restrict page lookup search to namespace given as url parameter
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function _search_query_pagelookup(Doku_Event &$event, $param) {
        //$this->_addNamespace2query($event->data['id']);
		//echo ("AAABBBCCC2");
    }

    /**
     * Extend query string with namespace, if it doesn't contain a namespace expression
     *
     * @param string &$query (reference) search query string
     */
    private function _addNamespace2query(&$query) {
        global $INPUT;

        $ns = cleanID($INPUT->str('ns'));
        if($ns) {
            //add namespace if user hasn't already provide one
            if(!preg_match('/(?:^| )(?:@|ns:)[\w:]+/u', $query, $matches)) {
                $query .= ' @' . $ns;
            }
        }
    }

}

// vim:ts=4:sw=4:et: