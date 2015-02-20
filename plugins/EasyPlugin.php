<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class EasyPlugin implements EventSubscriberInterface {

	public function onBeforeRequest(Request $request){
		// fired right before a request is being send to a proxy
	}
	
	public function onHeadersReceived(Response $response){
		// headers received - modify them
	}
	
	public function onCompleted(Response $response){
		// full response has been read.
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