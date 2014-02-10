<?php
error_reporting(E_ERROR | E_STRICT);
ini_set('display_errors', 'on');

require("classes/finder.php");

//Get Speaker
$finder = SonosFinder::getInstance();

//Use your player name as this parameter
$kitchen = $finder->getPlayerByName("Kitchen");

$kitchen->setListener('http://diskstation/listen.php/');

print_r ($finder->getNodeArray());


?>