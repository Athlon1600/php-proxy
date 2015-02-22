<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractPlugin implements EventSubscriberInterface {

	public function onBeforeRequest(FilterEvent $event){
		// fired right before a request is being sent to a proxy
	}
	
	public function onHeadersReceived(FilterEvent $event){
		// fired right after response headers have been fully received
	}
	
	public function onCompleted(FilterEvent $event){
		// fired after full response has been read
	}
	
	final public static function getSubscribedEvents(){
		return array(
			'request.before' => 'onBeforeRequest',
			'response.headers' => 'onHeadersReceived',
			'response.body' => 'onCompleted'
		);
	}
}

?>