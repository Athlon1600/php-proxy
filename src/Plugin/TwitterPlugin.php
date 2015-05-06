<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class TwitterPlugin extends AbstractPlugin {

	protected $url_pattern = 'twitter.com';

	public function onCompleted(ProxyEvent $event){
	
		// there is some problem with content-length when submitting form...
		$response = $event['response'];
		$content = $response->getContent();
		
		// remove all javascript
		$content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
			
		$response->setContent($content);
	}
}

?>