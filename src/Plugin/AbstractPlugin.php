<?php

namespace Proxy\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Proxy\Event\FilterEvent;

abstract class AbstractPlugin implements EventSubscriberInterface {

	// we're only interested in events that pass our url filter
	protected $url_pattern;
	
	public function onBeforeRequest(FilterEvent $event){
		// fired right before a request is being sent to a proxy
	}
	
	public function onHeadersReceived(FilterEvent $event){
		// fired right after response headers have been fully received - last chance to modify before sending it back to the user
	}
	
	public function onCompleted(FilterEvent $event){
		// fired after the full response=headers+body has been read - will only be called on "non-streaming" responses
	}
	
	// dispatch based on filter
	final public function route(FilterEvent $event){
	
		$url = $event->getRequest()->getUri();
		
		// url filter provided and current request url does not match it
		if($this->url_pattern && strpos($url, $this->url_pattern) === false){
			return;
		}
		
		switch($event->getName()){
		
			case 'request.before_send':
				$this->onBeforeRequest($event);
			break;
			
			case 'request.sent': 
				$this->onHeadersReceived($event);
			break;
			
			case 'request.complete':
				$this->onCompleted($event);
			break;
		}
	}
	
	final public static function getSubscribedEvents(){
		return array(
			'request.before_send' => 'route',
			'request.sent' => 'route',
			'request.complete' => 'route'
		);
	}
}

?>