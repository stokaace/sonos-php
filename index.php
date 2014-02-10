<?php
error_reporting(E_ERROR | E_STRICT);
ini_set('display_errors', 'on');

require_once("classes/finder.php");

//Get Speaker
$finder = SonosFinder::getInstance();

//Use your player name as this parameter
$kitchen = $finder->getPlayerByName("Kitchen");

echo $kitchen->volume(15);
//echo $kitchen->play();

/*  known issues and todo - resume tracks sometimes fails, spacing tracks out.  enhance: Error handling, xml parser, string parsers, pandora action
*/


?>