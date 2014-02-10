<?php
require_once("player.php");

class SonosFinder {
  
  private static $instance;

  private function __construct(){}
  
  // getInstance method 
  public static function getInstance() { 

    if(!self::$instance) { 
      self::$instance = new self(); 
    }
 
    return self::$instance; 

  } 

  private function findNode(){
  
	$PLAYER_SEARCH = "M-SEARCH * HTTP/1.1
HOST: 239.255.255.250:reservedSSDPport
MAN: ssdp:discover
MX: 1
ST: urn:schemas-upnp-org:device:ZonePlayer:1";

	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	socket_set_option($socket, 0, IP_MULTICAST_TTL, 2);
	socket_sendto($socket, $PLAYER_SEARCH, strlen($PLAYER_SEARCH), 0, '239.255.255.250', '1900');
	$from = '';
	$port = 0;
	$y = socket_recvfrom($socket, $resp, 2048, 0, $from, $port);
	socket_close($socket);

	//echo $resp;
	$a = explode("LOCATION: http://",$resp);
    $b = explode("/xml/", $a[1]);
      
	return $b[0];
	
  }
 
  public function getPlayers(){
	$players = array();
	
	$player_array = $this->getNodeArray();
	
	foreach($player_array as $player_info){
		$temp = SonosPlayer::getInstance($player_info['ip']);
		array_push($players, $temp);
	}
	
	return $players;
  }

  public function getPlayerByName($name){
	$players = $this->getNodeArray();
	$player = null;
	foreach($players as $player){
		if($player['name']==$name){
			return SonosPlayer::getInstance($player['ip']);
		}
	}
	return null;
  }
  
  public function getNodeArray(){
	$node = $this->findNode();
	$topology_url = "http://".$node."/status/topology";
	
	//grab topology XML
	$s = curl_init(); 
    curl_setopt($s,CURLOPT_URL,$topology_url); 
    curl_setopt($s,CURLOPT_TIMEOUT,3); 
    curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($s);
	$status = curl_getinfo($s,CURLINFO_HTTP_CODE); 
    curl_close($s); 
	
	$xml = new SimpleXMLElement($response);
	$players = array();
	
	
	foreach ($xml->ZonePlayers->ZonePlayer as $player){
		$url = (string)$player['location'];
		$a = explode("//",$url);
		$b = explode(":",$a[1]);
		$c = explode("/",$b[1]);
		$ip = $b[0];
		$port = $c[0];
		
		array_push(  $players, 
					 array(
						"url"=>$url, 
						"ip"=>$ip,
						"port"=>$port,
						"name"=>(string)$player
					 )
				  );
	};

	return($players);
	
  }
  
  
 


}


?>