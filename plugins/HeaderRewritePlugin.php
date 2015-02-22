<?php

class HeaderRewritePlugin extends AbstractPlugin {

	function onBeforeRequest(FilterEvent $event){
		
		// tell website that we only accept plain text
		$event->getRequest()->headers->remove('accept-encoding');
	}
	
	function onCompleted(FilterEvent $event){
	
		$response = $event->getResponse();
		
		// fix redirect - do redirect!
		if($response->headers->has('location')){
		
			$loc = $response->headers->get('location');
			
			$response->headers->set('location', SCRIPT_BASE.'?q='.encrypt_url($loc));
		}
		
		$remove = array("x-frame-options", "x-xss-protection",  "x-content-type-options");
		
		foreach($remove as $r){
			$response->headers->remove($r);
		}
		
		// forward only specified headers:
		$forward_only = array('content-type');
		
		// remove all caching headers!
		$response->headers->remove('age');
		$response->headers->remove('vary');
		$response->headers->remove('expires');
		
		// do not ever cache our proxy pages!
		$response->headers->set("cache-control", "no-store, no-cache, must-revalidate, max-age=0");
		$response->headers->set("pragma", "no-cache");
		
		// plain text only
		$response->headers->remove('transfer-encoding');
		$response->headers->remove('content-encoding');
	}
	
}

?>