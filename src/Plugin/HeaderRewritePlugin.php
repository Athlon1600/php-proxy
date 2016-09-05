<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class HeaderRewritePlugin extends AbstractPlugin {

	function onBeforeRequest(ProxyEvent $event){
		
		// tell target website that we only accept plain text without any transformations
		$event['request']->headers->set('accept-encoding', 'identity');

		// mask proxy referer
		$event['request']->headers->remove('referer');
	}
	
	function onHeadersReceived(ProxyEvent $event){

		// so stupid... onCompleted won't be called on "streaming" responses
		$response = $event['response'];
		$request_url = $event['request']->getUri();
		
		// proxify header location value
		if($response->headers->has('location')){
		
			$location = $response->headers->get('location');
			
			// just in case this is a relative url like: /en
			$response->headers->set('location', proxify_url($location, $request_url));
		}
		
		$code = $response->getStatusCode();
		$text = $response->getStatusText();

		if($code >= 400 && $code <= 600){
			throw new \Exception("Error accessing resource: {$code} - {$text}");
		}
		
		// we need content-encoding (in case server refuses to serve it in plain text)
		// content-length: final size of content sent to user may change via plugins, so it makes no sense to send old content-length
		$forward_headers = array('content-type', 'zzzcontent-length', 'accept-ranges', 'content-range', 'content-disposition', 'location', 'set-cookie');
		
		foreach($response->headers->all() as $name => $value){
			
			// is this one of the headers we wish to forward back to the client?
			if(!in_array($name, $forward_headers)){
				$response->headers->remove($name);
			}
		}
		
		if(!$response->headers->has('content-disposition')){
			
			$url_path = parse_url($request_url, PHP_URL_PATH);
			$filename = basename($url_path);
			
			$response->headers->set('Content-Disposition', 'filename="'.$filename.'"');
		}
		
		// do not ever cache our proxy pages!
		$response->headers->set("cache-control", "no-cache, no-store, must-revalidate");
		$response->headers->set("pragma", "no-cache");
		$response->headers->set("expires", 0);
	}
	
}

?>