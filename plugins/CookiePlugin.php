<?php

use Symfony\Component\HttpFoundation\Cookie;

class CookiePlugin extends EasyPlugin {

	const COOKIE_PREFIX = 'pc_';
	
	public function onBeforeRequest(ProxyEvent $event){
	
		$request = $event['request'];
		
		// rewrite the headers sent from the user
		$http_cookie = $request->headers->get("cookie");
		
		// remove
		$request->headers->remove("cookie");

		if(preg_match_all('@pc_(.+?)__(.+?)=([^;]+)@', $http_cookie, $matches, PREG_SET_ORDER)){
		
			foreach($matches as $match){
			
				$domain = str_replace("_", ".", $match[1]);
				
				$data['name'] = $match[2];
				$data['value'] = $match[3];
				$data['domain'] = $domain;
				
				$host = parse_url($request->getUri(), PHP_URL_HOST);
				
				// does this cookie belong to this domain?
				if(strpos($host, $domain) !== false){
					$request->headers->set('cookie', $data['name'].'='.$data['value'], false);
				}
			}
		}
	}
	
	// rewrite set-cookie header to something else
	public function onHeadersReceived(FilterEvent $event){
	
		// save cookies received from destination server
		//extract($event);
		
		$request = $event->getRequest();
		$response = $event->getResponse();
		
		// does our response send any cookies?
		$cookies = $response->headers->get('set-cookie', null, false);
		
		if($cookies){
		
			// remove set-cookie header and reconstruct it differently
			$response->headers->remove('set-cookie');
			
			// loop through each set-cookie
			foreach($cookies as $cookie_str){
			
				try {
				
					// valid instance of Cookie will hopefully be returned
					$cookie = $this->parse_cookie($cookie_str, $request->getUri());
					
					// construct our own cookie!!!!
					$name = 'pc_'.str_replace(".", "_", $cookie->getDomain()).'__'.$cookie->getName();
					$proxy_cookie = new Cookie($name, $cookie->getValue(), $cookie->getExpiresTime());
					
					// pass our new cookie to the client!!!
					$response->headers->setCookie($proxy_cookie);
				
				} catch (InvalidArgumentException $ex){
					//var_dump($ex->getMessage());
				}
			}
		}	
	}
	
	// adapted from browserkit
	private function parse_cookie($cookie_str, $url){
	
		$host = parse_url($url, PHP_URL_HOST);
		
		$data = array(
			'name' => '',
			'value' => '',
			'expire' => 0,
			'path' => '/',
			'domain' => $host,
			'secure' => false,
			'httpOnly' => true
		);
		
		// there should be at least one name=value pair
		$components = array_filter(array_map('trim', explode(';', $cookie_str)));
		
		foreach($components as $index => $comp){
		
			$parts = explode('=', $comp, 2);
			$key = trim($parts[0]);
			
			if(count($parts) == 1){
			
				// secure; HttpOnly; == 1
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
		
		extract($data);
		
		return new Cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
	}

}

?>