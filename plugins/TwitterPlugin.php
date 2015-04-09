<?php

class TwitterPlugin extends AbstractPlugin {

	protected $url_pattern = 'twitter.com';

	public function onCompleted(FilterEvent $event){
	
		// there is some problem with content-length when submitting form...
		$response = $event->getResponse();
		$content = $response->getContent();
		
		// remove all javascript
		$content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
			
		$response->setContent($content);
	}
}

?>