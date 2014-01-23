<?php

/////////////////
//Definitions
/////////////////

define("SOAP_TEMPLATE", '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body>{body}</s:Body></s:Envelope>');
define("TRANSPORT_ENDPOINT", '/MediaRenderer/AVTransport/Control');
define("SET_TRANSPORT_ACTION", '"urn:schemas-upnp-org:service:AVTransport:1#SetAVTransportURI"');
define("PLAY_URI_BODY_TEMPLATE", '<u:SetAVTransportURI xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><CurrentURI>{uri}</CurrentURI><CurrentURIMetaData>{meta}</CurrentURIMetaData></u:SetAVTransportURI>');
define("PLAY_ACTION", '"urn:schemas-upnp-org:service:AVTransport:1#Play"');
define("PLAY_BODY", '<u:Play xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Speed>1</Speed></u:Play>');
define("PLAY_RESPONSE", '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:PlayResponse xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"></u:PlayResponse></s:Body></s:Envelope>');
define("ENQUEUE_RESPONSE", '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetAVTransportURIResponse xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"></u:SetAVTransportURIResponse></s:Body></s:Envelope>');
define("DEVICE_ENDPOINT", '/DeviceProperties/Control');
define("SET_VOLUME_ACTION",'"urn:schemas-upnp-org:service:RenderingControl:1#SetVolume"');
define("SET_VOLUME_BODY_TEMPLATE",'<u:SetVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel><DesiredVolume>{volume}</DesiredVolume></u:SetVolume>');
define("SET_VOLUME_RESPONSE",'<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetVolumeResponse xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"></u:SetVolumeResponse></s:Body></s:Envelope>');
define("RENDERING_ENDPOINT",'/MediaRenderer/RenderingControl/Control');
define("GET_VOLUME_ACTION",'"urn:schemas-upnp-org:service:RenderingControl:1#GetVolume"');
define("GET_VOLUME_BODY",'<u:GetVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetVolume>');
define("GET_CUR_TRANSPORT_ACTION",'"urn:schema-upnp-org:service:AVTransport:1#GetTransportInfo"');
define("GET_CUR_TRANSPORT_BODY",'<u:GetTransportInfo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:GetTransportInfo></s:Body></s:Envelope>');
define("GET_CUR_TRACK_ACTION",'"urn:schemas-upnp-org:service:AVTransport:1#GetPositionInfo"');
define("GET_CUR_TRACK_BODY",'<u:GetPositionInfo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetPositionInfo>');
define("PLAYING", "PLAYING");
define("SEEK_ACTION", '"urn:schemas-upnp-org:service:AVTransport:1#Seek"');
define("PLAY_FROM_QUEUE_BODY_TEMPLATE", '<u:SetAVTransportURI xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><CurrentURI>{uri}</CurrentURI><CurrentURIMetaData></CurrentURIMetaData></u:SetAVTransportURI>');
define("SET_TRANSPORT_ACTION", '"urn:schemas-upnp-org:service:AVTransport:1#SetAVTransportURI"');
define("PLAY_FROM_QUEUE_RESPONSE", '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetAVTransportURIResponse xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"></u:SetAVTransportURIResponse></s:Body></s:Envelope>');
define("SEEK_TRACK_BODY_TEMPLATE", '<u:Seek xmlns:u="urn:schemas-upnp-org:service:AVTransport:1">
<InstanceID>0</InstanceID>
<Unit>TRACK_NR</Unit>
<Target>{track}</Target>
</u:Seek>');
define("SEEK_TIMESTAMP_BODY_TEMPLATE", '<u:Seek xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Unit>REL_TIME</Unit><Target>{timestamp}</Target></u:Seek>');


/*
$clock_speaker_ip = '192.168.1.XX';

//Get Speaker
$player = SonosPlayer::getInstance($clock_speaker_ip);

//Find out if the speaker is playing
$initial_status = $player->get_status();

$initial_track = array();

//Grab current play info
if($initial_status == PLAYING){
  $initial_track = $player->get_current_track_info();
}

$initial_volume = $player->volume();

//Turn down volume;
$player->volume(10);

$player->play_from_queue($initial_track['track']);

if($initial_status == PLAYING){
  $player->seek($initial_track['playhead']);
  $player->play();
}

//Turn volume back up to initial state
$player->volume($initial_volume);

*/


class SonosPlayer {

  private static $instance;

  private function __construct(){}
  
  private $ip = '';
  private $speaker_info = array();

  // getInstance method 
  public static function getInstance($ip) { 

    if(!self::$instance) { 
      self::$instance = new self(); 
    }
 
    self::$instance->ip = $ip;   

    return self::$instance; 

  } 


  public function play_uri($uri, $meta){

    $body = str_replace("{uri}", $uri, PLAY_URI_BODY_TEMPLATE);
    $body = str_replace("{meta}", $meta, $body);

    $response = $this->send_command(TRANSPORT_ENDPOINT, SET_TRANSPORT_ACTION, $body);

     if ($response == ENQUEUE_RESPONSE) {
       $this->play();
     }
     else {   
       return $response;
     }

  }


  public function play(){

    $response = $this->send_command(TRANSPORT_ENDPOINT, PLAY_ACTION, PLAY_BODY);
  
    if ($response == PLAY_RESPONSE){
      return true;
    }
    else{
      return $response;
    }

  }

  
  public function volume($volume=""){

    if ($volume != ""){
      $volume = max(0,min($volume, 100));
      $body = str_replace("{volume}",$volume, SET_VOLUME_BODY_TEMPLATE);

      $response = $this->send_command(RENDERING_ENDPOINT, SET_VOLUME_ACTION, $body);

      if ($response == SET_VOLUME_RESPONSE){
        return true;
      }
      else{
        return $response;
      }

    }    
    else{

      $response = $this->send_command(RENDERING_ENDPOINT, GET_VOLUME_ACTION, GET_VOLUME_BODY);
      $volume = $this->findXMLNode($response, "CurrentVolume");
      return intval($volume);
    }
 

  }

  public function get_status(){

    $response = $this->send_command(TRANSPORT_ENDPOINT, GET_CUR_TRANSPORT_ACTION, GET_CUR_TRANSPORT_BODY);
    $status = $this->findXMLNode($response, "CurrentTransportState");
    return $status;

  }
  
  public function get_current_track_info(){

    $response = $this->send_command(TRANSPORT_ENDPOINT, GET_CUR_TRACK_ACTION, GET_CUR_TRACK_BODY);
    $track = $this->findXMLNode($response, "Track");
    $time = $this->findXMLNode($response, "RelTime");

    //echo $response;
    $output['track']=$track;
    $output['playhead']=$time;
    return $output;

  }


  public function play_from_queue($index){

    if($this->speaker_info==array()){
      $this->get_speaker_info();
    }

    // first, set the queue itself as the source URI
    $uri = 'x-rincon-queue:'.$this->speaker_info['uid'].'#0';
    $body = str_replace("{uri}",$uri, PLAY_FROM_QUEUE_BODY_TEMPLATE);

    $response = $this->send_command(TRANSPORT_ENDPOINT, SET_TRANSPORT_ACTION, $body);

    if ($response != PLAY_FROM_QUEUE_RESPONSE){
      return $response;
    }
    else {
      // second, set the track number with a seek command
      $body = str_replace("{track}",$index, SEEK_TRACK_BODY_TEMPLATE);

      $response = $this->send_command(TRANSPORT_ENDPOINT, SEEK_ACTION, $body);
      if($response=0){
        return $response;
      }

      // finally, just play what's set
      return $this->play();
    }

  }

  public function get_speaker_info(){

    if ($this->speaker_info != array()){
      return $this->speaker_info;
    }
    else{
      $response = $this->send_command('/status/zp');
      $this->speaker_info['zone_name'] = $this->findXMLNode($response, "ZoneName");
      $this->speaker_info['uid'] = $this->findXMLNode($response, "LocalUID");
      return $this->speaker_info;
    }

  }

  public function seek($time){

    $body = str_replace("{timestamp}",$time,SEEK_TIMESTAMP_BODY_TEMPLATE);

    $response = $this->send_command(TRANSPORT_ENDPOINT, SEEK_ACTION, $body);

    if ($response==0){
      return $response;
    }
    else{
      return true;
    }

  }

  private function findXMLNode($string, $nodeName){

      $a = explode("<".$nodeName.">",$string);
      $b = explode("</".$nodeName.">", $a[1]);
      return $b[0];

  }

  private function send_command($endpoint, $action="", $body=""){
  
    $headers = array("Content-Type:text/xml","SOAPACTION:$action");

    $soap = str_replace("{body}", $body, SOAP_TEMPLATE);
  
    $url = 'http://' . $this->ip . ':1400' . $endpoint;
    //echo $url;
    $s = curl_init(); 
    curl_setopt($s,CURLOPT_URL,$url); 
    
    if($action!=""){
      curl_setopt($s,CURLOPT_HTTPHEADER,$headers); 
    }

    curl_setopt($s,CURLOPT_TIMEOUT,4); 

    if($body!=""){
      curl_setopt($s,CURLOPT_POST,true); 
      curl_setopt($s,CURLOPT_POSTFIELDS,$soap);        
    }

    curl_setopt($s,CURLOPT_HEADER,false); 
    curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
  
    $response = curl_exec($s);

    $status = curl_getinfo($s,CURLINFO_HTTP_CODE); 

    curl_close($s); 

    return $response;

  }


}








?>