<?php

use Symfony\Component\HttpFoundation\Cookie;

class AccessControlPlugin extends AbstractPlugin {

	private function get_country_code($ip){
	
		// check country using this API
		$json = file_get_contents("https://freegeoip.net/json/".$ip);
		$json = json_decode($json, true);
		
		$country_code = $json['country_code'];
		
		return $country_code;
	}
	
	// TODO: fix ugly code
	public function onBeforeRequest(FilterEvent $event){
	
		$url = $event->getRequest()->getUri();
		
		global $config;
		
		$whitelist = $config->get("ac.url_whitelist");
		$blacklist = $config->get("ac.url_blacklist");
		
		if($whitelist){
		
			foreach($whitelist as $p){
			
				if(strpos($url, $p) !== false){
					return;
				}
			}
			
			throw new Exception("Access to URL: {$url} has been blocked using whitelist");
		}
		
		if($blacklist){
		
			foreach($blacklist as $p){
			
				if(strpos($url, $p) !== false){
				
					throw new Exception("Access to URL: [{$url}] has been blocked using a directive: [{$p}]");
				}
				
			}
		}
		
		
		// do we wish to block any countries?
		if($config->get('ac.country_blacklist')){
		
			if(!$config->get('secret_key')){
				throw new Exception("config.php error... [secret_key] must not be empty!");
			}
		
			$ip = $_SERVER['REMOTE_ADDR'];
			$country_code = null;
			
			if(isset($_COOKIE['country_code'])){
		
				$value = $_COOKIE['country_code'];
				
				list($cc, $hash) = explode(",", $value);
				
				if(md5($ip.'@'.$cc.'@'.$config->get('secret_key')) == $hash){
					$country_code = $cc;
				}
			}
			
			// could not find a cookie with previously stored country data
			if(!$country_code){
			
				$country_code = $this->get_country_code($ip);
				
				// set a cookie so we don't have to check this on every connect
				$hash = md5($ip.'@'.$country_code.'@'.$config->get('secret_key'));
				$cookie_val = $country_code.','.$hash;
				
				$event->getResponse()->headers->setCookie(new Cookie("country_code", $cookie_val, time() + 60*60*24*7));
			}
			
			if(in_arrayi($country_code, $config->get('ac.country_blacklist'))){
				throw new Exception('Connections from your country have been blocked');
			}
			
		}
		
	}
	


}

?>