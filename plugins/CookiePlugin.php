<?php

class CookiePlugin extends AbstractPlugin {

	public function onBeforeRequest(FilterEvent $event){
	
		// load and send appropriate cookies to our destination
		$http_cookie = @$_SERVER['HTTP_COOKIE'];
		
		if(preg_match_all('@dc_(.+?)__(.+?)=([^;]+)@', $http_cookie, $matches, PREG_SET_ORDER)){
		
			foreach($matches as $match){
			
				$domain = str_replace("_", ".", $match[1]);
				
				$data['name'] = $match[2];
				$data['value'] = $match[3];
				$data['domain'] = $domain;
				
				$event->getRequest()->headers->set($data['name'], $data['value'], false);
			}
		}
	}
	
	private function parse($header){
	
		$data = array();
		
		// there should be at least one name=value pair
		$components = array_filter(array_map('trim', explode(';', $header)));
		
		foreach($components as $index => $comp){
		
			$parts = explode('=', $comp, 2);
			$key = trim($parts[0]);
			
			if(count($parts) == 1){
			
				$data[$key] = true;
				
			} else {
				
				$value = trim($parts[1]);
				
				if($index == 0){
					$data['name'] = $key;
					$data['value'] = $value;
				} else {
					$data[$key] = $value;
				}
			}
		}
		
		return $data;
	}
	
	public function onBeforeHeaders(FilterEvent $event){
	
		// save cookies received from destination server
		$request = $event->getRequest();
		$response = $event->getResponse();
		
		$set_cookie = $response->headers->get('set-cookie', 0, false);
		
		foreach($set_cookie as $c){
		
			$data = $this->parse($c);
			
			// domain
			$domain = $request->getHost();
			
			// store all cookies on user's browser
			@$cookie_name = 'dc_'.str_replace(".", "_", $domain).'__'.$data['name'];
			
			@setcookie($cookie_name, $data['value'], time() + 60*60);
		}
		
		// cookies were already set - discard the rest
		$response->headers->remove('set-cookie');
	}

}

?>