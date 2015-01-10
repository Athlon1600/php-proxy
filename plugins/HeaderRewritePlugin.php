<?php

class HeaderRewritePlugin extends AbstractPlugin {

	function onBeforeRequest(FilterEvent $event){
		
		// we accept plain text only
		$event->getRequest()->headers->remove('accept-encoding');
		$event->getRequest()->headers->remove('host');
	}
	
	function onBeforeResponse(FilterEvent $event){
	
		$response = $event->getResponse();
		
		// fix redirect - do redirect!
		if($response->headers->has('location')){
		
			$loc = $response->headers->get('location');
			
			$response->headers->set('location', SCRIPT_BASE.'?q='.encrypt_url($loc));
		}
		
		// do not ever cache
		$response->headers->set("cache-control", "no-store, no-cache, must-revalidate, max-age=0");
		$response->headers->set("pragma", "no-cache");
		
		// plain text only
		//$response->headers->remove('transfer-encoding');
		//$response->headers->remove('content-encoding');
	}
	
}

?>