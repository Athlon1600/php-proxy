<?php

class TwitterPlugin extends AbstractPlugin {

	public function onBeforeRequest(FilterEvent $event){
		
		
		$url = $event->getRequest()->getUri();
		
		if(strpos($url, "twitter.com") !== false){
		
			//die("twitter");
		
			$req = $event->getRequest();
			
			$req->headers->remove("content-length");
			


			

			
		}
		
	
	}

	public function onCompleted(FilterEvent $event){
	

	
	
			
		$url = $event->getRequest()->getUri();
		
		if(strpos($url, "twitter.com") !== false){
		
			//die("twitter");
			
			$res = $event->getResponse();
			
			$str = $res->getContent();
			
			$str = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $str);
			
			$res->setContent($str);
			
			
		}
		
		
		
		
	
	}
	



}

?>