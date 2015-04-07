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
			
			$response->headers->set('location', SCRIPT_BASE.'?q='.encrypt_url($loc));
		}
		
			// plain text only
		$response->headers->remove('transfer-encoding');
		$response->headers->remove('content-encoding');
		
		return;
		

		$remove = array('age', 'vary', 'expires', 'transfer-encoding', 'content-encoding', 
		'x-frame-options', 'x-xss-protection', 'x-content-type-options', 'etag');
		
		// remove bad headers
		foreach($remove as $r){
			//$response->headers->remove($r);
		}
		
		$response->headers->set('connection', 'keep-alive');
		
		// forward only these headers back to the client:
		$forward_only = array('content-type');
		
		// do not ever cache our proxy pages!
		//$response->headers->set("cache-control", "no-store, no-cache, must-revalidate, max-age=0");
		//$response->headers->set("pragma", "no-cache");
		
	/*
	Forbidden

You don't have permission to access /php-proxy/index.php/ on this server.
*/

	}
	
	function onCompleted(FilterEvent $event){
		// do nothing
		
		$a = $event->getResponse()->getContent();
		
		//header('content-type: video/mp4');
		
		//var_dump($a);
		
		//var_dump(strlen($a));
		
		///exit;
	}
	
}

?>