<?php

include("classes/player.php");

/*
  This script example demonstrates an action which interrupts any music already playing, plays another track and then restores the initial player state.
  
  clock
  psa
  
*/


//This is just an example that can be set to your Sonos player IP.  
$speaker_ip = '192.168.1.XX';

//Get Speaker
$player = SonosPlayer::getInstance($speaker_ip);

//Find out if the speaker is playing
$initial_status = $player->get_status();

$initial_track = array();

//Grab current play info
if($initial_status == SonosPlayer::PLAYING){
  $initial_track = $player->get_current_track_info();
}

//Grab initial volume
$initial_volume = $player->volume();

//Do something here that interrupts playing
$player->volume(10);


//Restore initial track
$player->play_from_queue($initial_track['track']);

//Restore initial playhead state if a track was playing
if($initial_status == PLAYING){
  $player->seek($initial_track['playhead']);
  $player->play();
}

//Turn volume back up to initial state
$player->volume($initial_volume);


?>