<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractPlugin implements EventSubscriberInterface {

	public function onBeforeRequest(FilterEvent $event){
		// must override
	}
	
	public function onBeforeHeaders(FilterEvent $event){
		// must override
	}
	
	public function onBeforeResponse(FilterEvent $event){
		// must override
	}
	
	final public static function getSubscribedEvents(){
		return array(
			'request.before' => 'onBeforeRequest',
			'response.headers' => 'onBeforeHeaders',
			'response.body' => 'onBeforeResponse'
		);
	}
}

?>