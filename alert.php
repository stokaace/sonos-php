<?php
require_once("classes/player.php");
require_once("classes/finder.php");

$sound_dir = 'http://diskstation/clock/';
$phrase = "pickup.mp3";

//Get Speaker
$finder = SonosFinder::getInstance();

//Use your player name as this parameter
$player = $finder->getPlayerByName("Kitchen");

//Speak X phrase for Y seconds at Z volume and then return to playlist
$player->psa($sound_dir.$phrase, 10, 20);

?>