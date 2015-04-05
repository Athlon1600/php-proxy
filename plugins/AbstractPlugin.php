 <?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractPlugin implements EventSubscriberInterface {

	// we're only interested in events that pass our filters
	protected $request_filter = array(
	
	);
	
	public function onBeforeRequest(FilterEvent $event){
		// fired right before a request is being sent to a proxy
	}
	
	public function onHeadersReceived(FilterEvent $event){
		// fired right after response headers have been fully received
	}
	
	public function onCompleted(FilterEvent $event){
		// fired after the full response=headers+body has been read
	}
	
	final public function route(FilterEvent $event){
	
		$url = $event->getRequest()->getUri();
		
		// url filter provided and current request url does not match it
		if(isset($this->request_filter['url']) && preg_match('/'.$this->request_filter['url'].'/i', $url) !== 1){
			return;
		}
		
		switch($event->getName()){
		
			case 'request.before':
				$this->onBeforeRequest($event);
			break;
			
			case 'response.headers': 
				$this->onHeadersReceived($event);
			break;
			
			case 'response.body':
				$this->onCompleted($event);
			break;
		}
	}
	
	final public static function getSubscribedEvents(){
		return array(
			'request.before' => 'route',
			'response.headers' => 'route',
			'response.body' => 'route'
		);
	}
}

?>