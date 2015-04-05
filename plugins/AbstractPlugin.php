 <?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
		// fired after the full response=headers+body has been read
	}
	
	// dispatch based on filter
	final public function route(FilterEvent $event){
	
		$url = $event->getRequest()->getUri();
		
		// url filter provided and current request url does not match it
		if($this->url_pattern && preg_match('/'.$this->url_pattern.'/i', $url) !== 1){
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