<?php

class HeaderRewritePlugin extends AbstractPlugin {

	function onBeforeRequest(FilterEvent $event){
		
		// tell target website that we only accept plain text
		$event->getRequest()->headers->remove('accept-encoding');

		// mask proxy referer
		$event->getRequest()->headers->remove('referer');
	}
	
	function onHeadersReceived(FilterEvent $event){
	
		// so stupid... onCompleted won't be called on "streaming" responses
		$response = $event->getResponse();
		
		// proxify header location value
		if($response->headers->has('location')){
		
			$loc = $response->headers->get('location');
			
			// we use rel2abs in case location has relative path such as /us
			$loc = rel2abs($loc, $event->getRequest()->getUri());
			
			$response->headers->set('location', SCRIPT_BASE.'?q='.encrypt_url($loc));
		}
		
		$code = $response->getStatusCode();
		$text = $response::$statusTexts[$code];

		if($code >= 400 && $code <= 600){
			throw new Exception("Error accessing resource: {$code} - {$text}");
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