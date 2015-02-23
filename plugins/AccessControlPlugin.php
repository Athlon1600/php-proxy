<?php

class AccessControlPlugin extends AbstractPlugin {

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
		
		
	}
	


}

?>