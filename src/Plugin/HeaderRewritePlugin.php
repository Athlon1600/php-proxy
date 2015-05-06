<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class HeaderRewritePlugin extends AbstractPlugin {

	function onBeforeRequest(ProxyEvent $event){
		
		// tell target website that we only accept plain text
		$event['request']->headers->remove('accept-encoding');

		// mask proxy referer
		$event['request']->headers->remove('referer');
	}
	
	function onHeadersReceived(ProxyEvent $event){
	
		// so stupid... onCompleted won't be called on "streaming" responses
		$response = $event['response'];
		
		// proxify header location value
		if($response->headers->has('location')){
		
			$location = $response->headers->get('location');
			
			$response->headers->set('location', proxify_url($location));
		}
		
		$code = $response->getStatusCode();
		$text = $response::$statusTexts[$code];

		if($code >= 400 && $code <= 600){
			throw new \Exception("Error accessing resource: {$code} - {$text}");
		}
		
		// TODO: convert this to a whitelist rather than a blacklist
		$remove = array('age', 'vary', 'expires', 'transfer-encoding', 'content-encoding', 'x-frame-options', 'x-xss-protection', 'x-content-type-options', 'etag');
		
		foreach($remove as $r){
			$response->headers->remove($r);
		}
		
		// do not ever cache our proxy pages!
		$response->headers->set("cache-control", "no-store, no-cache, must-revalidate, max-age=0");
		$response->headers->set("pragma", "no-cache");
	}
	
}

?>