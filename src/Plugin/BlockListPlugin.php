<?php

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;
use Proxy\Config;

// https://proxylist.hidemyass.com/upload/
// TODO: this file is not found to be existant in ./plugins/ when namespace is specified
class BlockListPlugin extends AbstractPlugin {
	
	function onBeforeRequest(ProxyEvent $event){
		
		$user_ip = $_SERVER['REMOTE_ADDR'];
		$user_ip_long = sprintf('%u', ip2long($user_ip));
		
		$url = $event['request']->getUrl();
		$url_host = parse_url($url, PHP_URL_HOST);
		
		$fnc_custom = Config::get('blocklist.custom');
		if(is_callable($fnc_custom)){
			
			$ret = call_user_func($fnc_custom, compact('user_ip', 'user_ip_long', 'url', 'url_host') );
			if(!$ret){
				throw new \Exception("Error: Access Denied!");
			}
			
			return;
		}
		
		// url filter!
		$url_block = (array)Config::get('blocklist.url_block');
		foreach($url_block as $ub){
			
			if(strpos($url, $ub) !== false){
				throw new \Exception("Error: Access to {$url} has been blocked!");
				return;
			}
		}
		
		/*
		1. Wildcard format:     1.2.3.*
		2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
		3. Start-End IP format: 1.2.3.0-1.2.3.255
		*/
		$ip_match = false;
		$action_block = true;
		
		if(Config::has('blocklist.ip_allow')){
			$ip_match = Config::get('blocklist.ip_allow');
			$action_block = false;
		} else if(Config::has('blocklist.ip_block')){
			$ip_match = Config::get('blocklist.ip_block');
		}
		
		if($ip_match){
			$m = re_match($ip_match, $user_ip);
			
			// ip matched and we are in block_mode
			// ip NOT matched and we are in allow mode
			if( ($m && $action_block) || (!$m && !$action_block)){
				throw new \Exception("Error: Access denied!");
			}
		}
	}
}

?>