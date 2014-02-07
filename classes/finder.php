<?php

class SonosFinder {
  
  private static $instance;

  private function __construct(){}
  
  
  // getInstance method 
  public static function getInstance($ip) { 

    if(!self::$instance) { 
      self::$instance = new self(); 
    }
 
    self::$instance->ip = $ip;   

    return self::$instance; 

  } 


 


}








?>