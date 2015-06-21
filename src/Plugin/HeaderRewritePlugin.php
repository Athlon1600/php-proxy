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
		
		// TODO: convert this to a whitelist rather than a blacklist
		// we need content-enconding (in case server refuses to serve it in plain text) whitelisted
		$remove = array('age', 'vary', 'expires', 'transfer-encoding', 'x-frame-options', 'x-xss-protection', 'x-content-type-options', 'etag');	
	
		foreach($remove as $r){
			$response->headers->remove($r);
		}
		
		// do not ever cache our proxy pages!
		$response->headers->set("cache-control", "no-store, no-cache, must-revalidate, max-age=0");
		$response->headers->set("pragma", "no-cache");
	}
	
}

?>