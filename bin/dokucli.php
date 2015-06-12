<?php
if ('cli' != php_sapi_name()) die();

ini_set('memory_limit','128M');
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../').'/');
define('NOSESSION',1);
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/parserutils.php');


$source = stream_get_contents(STDIN);
$info = array();
echo p_render('xhtml',p_get_instructions($source),$info);
?>