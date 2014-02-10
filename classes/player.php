<?php


/**
  * queue_uri() 
  * play()
  * pause()
  * volume()
  * get_status()
  * get_current_track_info()
  * play_from_queue()
  * get_speaker _info()
  * seek()
  * psa()  			- experimental
  * setListener()  	- experimental
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

  public function queue_uri($uri, $meta="na"){

    $body = str_replace("{uri}", $uri, self::PLAY_URI_BODY_TEMPLATE);
    $body = str_replace("{meta}", $meta, $body);

    $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::SET_TRANSPORT_ACTION, $body);

     if ($response == self::ENQUEUE_RESPONSE) {
       return true;
     }
     else {   
       return $this->parse_error($response);
     }

  }

  public function play(){

    $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::PLAY_ACTION, self::PLAY_BODY);
  
    if ($response == self::PLAY_RESPONSE){
      return true;
    }
    else{
      return $this->parse_error($response);
    }

  }

  public function pause(){

    $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::PAUSE_ACTION, self::PAUSE_BODY);
  
    if ($response == self::PAUSE_RESPONSE){
      return true;
    }
    else{
      return $this->parse_error($response);
    }

  }
  
  
  public function volume($volume=""){

    if ($volume != ""){
      $volume = max(0,min($volume, 100));
      $body = str_replace("{volume}",$volume, self::SET_VOLUME_BODY_TEMPLATE);

      $response = $this->send_command(self::RENDERING_ENDPOINT, self::SET_VOLUME_ACTION, $body);

      if ($response == self::SET_VOLUME_RESPONSE){
        return true;
      }
      else{
        return $this->parse_error($response);
      }

    }    
    else{

      $response = $this->send_command(self::RENDERING_ENDPOINT, self::GET_VOLUME_ACTION, self::GET_VOLUME_BODY);
      $volume = $this->findXMLNode($response, "CurrentVolume");
      return intval($volume);
	  
    }

  }

  public function get_status(){

    $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::GET_CUR_TRANSPORT_ACTION, self::GET_CUR_TRANSPORT_BODY);
    $status = $this->findXMLNode($response, "CurrentTransportState");
    return $status;

  }
  
  public function get_current_track_info(){

    $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::GET_CUR_TRACK_ACTION, self::GET_CUR_TRACK_BODY);
    $track = $this->findXMLNode($response, "Track");
    $time = $this->findXMLNode($response, "RelTime");
    $output = array(); 
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
    $body = str_replace("{uri}",$uri, self::PLAY_FROM_QUEUE_BODY_TEMPLATE);

    $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::SET_TRANSPORT_ACTION, $body);

    if ($response != self::PLAY_FROM_QUEUE_RESPONSE){
      return $this->parse_error($response);
    }
    else {
      // second, set the track number with a seek command
      $body = str_replace("{track}",$index, self::SEEK_TRACK_BODY_TEMPLATE);

      $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::SEEK_ACTION, $body);
      if($response=0){
        return $this->parse_error($response);
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

    $body = str_replace("{timestamp}",$time,self::SEEK_TIMESTAMP_BODY_TEMPLATE);

    $response = $this->send_command(self::TRANSPORT_ENDPOINT, self::SEEK_ACTION, $body);

    if ($response==0){
      return $this->parse_error($response);
    }
    else{
      return true;
    }

  }
  
  public function psa($uri, $duration, $volume=''){
  
    //Find out if the speaker is playing
    $initial_status = $this->get_status();

    $initial_track = array();

    //Grab current play info
    if($initial_status == self::PLAYING){
      $initial_track = $this->get_current_track_info();
    }

    //Grab initial volume
    $initial_volume = $this->volume();
	//echo $initial_volume;

	if(!empty($volume)){
	  $this->volume($volume);
	}
	
	$this->queue_uri($uri);
	$this->play();
	sleep($duration);
	
	//Restore initial track
	$this->play_from_queue($initial_track['track']);

	//Restore initial playhead state if a track was playing
	if($initial_status == self::PLAYING){
	  $this->seek($initial_track['playhead']);
	  $this->play();
	}

	//Turn volume back up to initial state
	$this->volume($initial_volume);
  
  }

  /*
   *   
   */
  private function findXMLNode($string, $nodeName){

      $a = explode("<".$nodeName.">",$string);
      $b = explode("</".$nodeName.">", $a[1]);
      return $b[0];

  }

  private function parse_error($response){

    return $response;
  
  }
  
  /*
   *   
   */
  private function send_command($endpoint, $action="", $body=""){
  
    $headers = array("Content-Type:text/xml","SOAPACTION:$action");

    $soap = str_replace("{body}", $body, self::SOAP_TEMPLATE);
  
    $url = 'http://' . $this->ip . ':1400' . $endpoint;
    
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
  
  public function setListener($listener_url){
    $listener_timeout = 60*60*24*3;
    $headers = array("TIMEOUT:Second-".$listener_timeout, 
					"CALLBACK:<".$listener_url.">",
					"NT:upnp:event"
					);

    $url = 'http://' . $this->ip . ':1400/MediaRenderer/AVTransport/Event';
   
	$s = curl_init(); 
		curl_setopt($s,CURLOPT_URL,$url); 
		
		curl_setopt($s,CURLOPT_HTTPHEADER,$headers); 
		
		curl_setopt($s,CURLOPT_TIMEOUT,4); 

		curl_setopt($s, CURLOPT_CUSTOMREQUEST, 'SUBSCRIBE');
		//curl_setopt($s, CURLOPT_POST, true);
		
		curl_setopt($s,CURLOPT_HEADER,false); 
		curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
	  
		$response = curl_exec($s);

		$status = curl_getinfo($s,CURLINFO_HTTP_CODE); 
    curl_close($s); 
	
    return $response;

  return true;
  }
  
  const SOAP_TEMPLATE = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body>{body}</s:Body></s:Envelope>';
  const TRANSPORT_ENDPOINT = '/MediaRenderer/AVTransport/Control';
  const SET_TRANSPORT_ACTION = '"urn:schemas-upnp-org:service:AVTransport:1#SetAVTransportURI"';
  const PLAY_URI_BODY_TEMPLATE = '<u:SetAVTransportURI xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><CurrentURI>{uri}</CurrentURI><CurrentURIMetaData>{meta}</CurrentURIMetaData></u:SetAVTransportURI>';
  const PLAY_ACTION = '"urn:schemas-upnp-org:service:AVTransport:1#Play"';
  const PLAY_BODY = '<u:Play xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Speed>1</Speed></u:Play>';
  const PLAY_RESPONSE = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:PlayResponse xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"></u:PlayResponse></s:Body></s:Envelope>';
  const ENQUEUE_RESPONSE = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetAVTransportURIResponse xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"></u:SetAVTransportURIResponse></s:Body></s:Envelope>';
  const DEVICE_ENDPOINT = '/DeviceProperties/Control';
  const SET_VOLUME_ACTION = '"urn:schemas-upnp-org:service:RenderingControl:1#SetVolume"';
  const SET_VOLUME_BODY_TEMPLATE = '<u:SetVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel><DesiredVolume>{volume}</DesiredVolume></u:SetVolume>';
  const SET_VOLUME_RESPONSE = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetVolumeResponse xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"></u:SetVolumeResponse></s:Body></s:Envelope>';
  const RENDERING_ENDPOINT = '/MediaRenderer/RenderingControl/Control';
  const GET_VOLUME_ACTION = '"urn:schemas-upnp-org:service:RenderingControl:1#GetVolume"';
  const GET_VOLUME_BODY = '<u:GetVolume xmlns:u="urn:schemas-upnp-org:service:RenderingControl:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetVolume>';
  const GET_CUR_TRANSPORT_ACTION = '"urn:schema-upnp-org:service:AVTransport:1#GetTransportInfo"';
  const GET_CUR_TRANSPORT_BODY = '<u:GetTransportInfo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:GetTransportInfo></s:Body></s:Envelope>';
  const GET_CUR_TRACK_ACTION = '"urn:schemas-upnp-org:service:AVTransport:1#GetPositionInfo"';
  const GET_CUR_TRACK_BODY = '<u:GetPositionInfo xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Channel>Master</Channel></u:GetPositionInfo>';
  const PLAYING = 'PLAYING';
  const SEEK_ACTION = '"urn:schemas-upnp-org:service:AVTransport:1#Seek"';
  const PLAY_FROM_QUEUE_BODY_TEMPLATE = '<u:SetAVTransportURI xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><CurrentURI>{uri}</CurrentURI><CurrentURIMetaData></CurrentURIMetaData></u:SetAVTransportURI>';
  
  const PLAY_FROM_QUEUE_RESPONSE = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:SetAVTransportURIResponse xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"></u:SetAVTransportURIResponse></s:Body></s:Envelope>';
  const SEEK_TRACK_BODY_TEMPLATE = '<u:Seek xmlns:u="urn:schemas-upnp-org:service:AVTransport:1">
<InstanceID>0</InstanceID>
<Unit>TRACK_NR</Unit>
<Target>{track}</Target>
</u:Seek>';
  const SEEK_TIMESTAMP_BODY_TEMPLATE = '<u:Seek xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Unit>REL_TIME</Unit><Target>{timestamp}</Target></u:Seek>';
  const PAUSE_ACTION =  '"urn:schemas-upnp-org:service:AVTransport:1#Pause"';
  const PAUSE_BODY = '<u:Pause xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Speed>1</Speed></u:Pause>';
  const PAUSE_RESPONSE = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><s:Body><u:PauseResponse xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"></u:PauseResponse></s:Body></s:Envelope>';

  
}



?>