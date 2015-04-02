<?php

class HeaderRewritePlugin extends AbstractPlugin {

	function onBeforeRequest(FilterEvent $event){
		
		// tell website that we only accept plain text
		$event->getRequest()->headers->remove('accept-encoding');
		

		//$event->getResponse()->setContent('response text');
		////$event->stopPropagation();
		
		//$event->getRequest()->headers->set('user-agent', 'Mozilla/5.0 (Linux; U; Android 4.0.3; ko-kr; LG-L160L Build/IML74K) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');
		
		
	}
	
	function onHeadersReceived(FilterEvent $event){
	
		$url = $event->getRequest()->getUri();
		
		$file = './storage/cache/'.md5($url);
		
		if(file_exists($file)){
		
			//$event->getResponse()->setContent
		
		}
	
	}
	
	
	function onCompleted(FilterEvent $event){
	
		$response = $event->getResponse();
		
		// fix redirect - do redirect!
		if($response->headers->has('location')){
		
			$loc = $response->headers->get('location');
			
			$response->headers->set('location', SCRIPT_BASE.'?q='.encrypt_url($loc));
		}
		
		// should we cache this?
		$url = $event->getRequest()->getUri();
		
		if($content_type = $event->getResponse()->headers->get("content-type")){
		
			$content_type = clean_content_type($content_type);
			
			$cache_types = array("text/javascript", "text/css", "image/jpeg", "image/gif", "image/png");
			
			if(in_array($content_type, $cache_types)){
				//file_put_contents('./storage/cache/'.md5($url), $response->getContent());
			}
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