<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class CookiePlugin extends AbstractPlugin {

	const COOKIE_PREFIX = 'pc';
	
	public function onBeforeRequest(ProxyEvent $event){
	
		$request = $event['request'];
		
		// cookie sent by the browser to the server
		$http_cookie = $request->headers->get("cookie");
		
		// remove old cookie header and rewrite it
		$request->headers->remove("cookie");
		
		/*
			When the user agent generates an HTTP request, the user agent MUST NOT attach more than one Cookie header field.
			http://tools.ietf.org/html/rfc6265#section-5.4
		*/
		$send_cookies = array();
		
		// extract "proxy cookies" only
		// A Proxy Cookie would have  the following name: COOKIE_PREFIX_domain-it-belongs-to__cookie-name
		if(preg_match_all('@pc_(.+?)__(.+?)=([^;]+)@', $http_cookie, $matches, PREG_SET_ORDER)){
		
			foreach($matches as $match){
			
				$cookie_name = $match[2];
				$cookie_value = $match[3];
				$cookie_domain = str_replace("_", ".", $match[1]);
				
				// what is the domain or our current URL?
				$host = parse_url($request->getUri(), PHP_URL_HOST);
				
				// does this cookie belong to this domain?
				// sometimes domain begins with a DOT indicating all subdomains - deprecated but still in use on some servers...
				if(strpos($host, $cookie_domain) !== false){
					$send_cookies[] = $cookie_name.'='.$cookie_value;
				}
			}
		}
		
		// do we have any cookies to send?
		if($send_cookies){
			$request->headers->set('cookie', implode("; ", $send_cookies));
		}
	}
	
	// cookies received from a target server via set-cookie should be rewritten
	public function onHeadersReceived(ProxyEvent $event){
	
		$request = $event['request'];
		$response = $event['response'];
		
		// does the response send any cookies?
		$set_cookie = $response->headers->get('set-cookie');
		
		if($set_cookie){
		
			// remove set-cookie header and reconstruct it differently
			$response->headers->remove('set-cookie');
			
			// loop through each set-cookie line
			foreach( (array)$set_cookie as $line){
			
				// parse cookie data as array from header line
				$cookie = $this->parse_cookie($line, $request->getUri());
				
				// construct a "proxy cookie" whose name includes the domain to which this cookie belongs to
				// replace dots with underscores as cookie name can only contain alphanumeric and underscore
				$cookie_name = sprintf("%s_%s__%s", self::COOKIE_PREFIX, str_replace('.', '_', $cookie['domain']), $cookie['name']);
				
				// append a simple name=value cookie to the header - no expiration date means that the cookie will be a session cookie
				$event['response']->headers->set('set-cookie', $cookie_name.'='.$cookie['value'], false);
			}
		}
	}
	
	// adapted from browserkit
	private function parse_cookie($line, $url){

		$host = parse_url($url, PHP_URL_HOST);
		
		$data = array(
			'name' => '',
			'value' => '',
			'domain' => $host,
			'path' => '/',
			'expires' => 0,
			'secure' => false,
			'httpOnly' => true
		);
		
		$line = preg_replace('/^Set-Cookie2?: /i', '', trim($line));
		
		// there should be at least one name=value pair
		$pairs = array_filter(array_map('trim', explode(';', $line)));
		
		foreach($pairs as $index => $comp){
		
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
		
		return $data;
	}
}

?>