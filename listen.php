<?php
error_reporting(E_ERROR | E_STRICT);
ini_set('display_errors', 'on');

$body = (string)file_get_contents('php://input');

$body = str_replace("&gt;",">", $body);
$body = str_replace("&lt;","<", $body);
$body = str_replace("&amp;","$", $body);
$body = str_replace("&quot;",'"', $body);

$body = str_replace('$gt;',">", $body);
$body = str_replace('$lt;',"<", $body);
$body = str_replace('$amp;',"$", $body);
$body = str_replace('$quot;','"', $body);


//logit($body);

//PAUSED_PLAYBACK, PLAYING, TRANSITIONING, STOPPED

$a = explode('<TransportState val="', $body);
if(isset($a[1])){
	$b = explode('"/>', $a[1]);
}else{
	$b[0]='';
}

logit($_SERVER['REMOTE_ADDR']." - ".$b[0]);


function logit($msg){

	$fp = fopen('listen.log', 'a');
	fwrite($fp, date("Y-m-d H:i:s")." ");
	fwrite($fp, $msg."\n");
	fclose($fp);
}


?>