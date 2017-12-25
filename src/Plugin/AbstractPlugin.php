<?php

namespace Proxy\Plugin;

use Proxy\Event\ProxyEvent;

abstract class AbstractPlugin {
	
	// apply these methods only to those events whose request URL passes this filter
	protected $url_pattern;
	
	public function onBeforeRequest(ProxyEvent $event){
		// fired right before a request is being sent to a proxy
	}
	
	public function onHeadersReceived(ProxyEvent $event){
		// fired right after response headers have been fully received - last chance to modify before sending it back to the user
	}
	
	public function onCurlWrite(ProxyEvent $event){
		// fired as the data is being written piece by piece
	}
	
	public function onCompleted(ProxyEvent $event){
		// fired after the full response=headers+body has been read - will only be called on "non-streaming" responses
	}
	
	final public function subscribe($dispatcher){
		
		$dispatcher->addListener('request.before_send', function($event){
			$this->route('request.before_send', $event);
		});
		
		$dispatcher->addListener('request.sent', function($event){
			$this->route('request.sent', $event);
		});
		
		$dispatcher->addListener('curl.callback.write', function($event){
			$this->route('curl.callback.write', $event);
		});
		
		$dispatcher->addListener('request.complete', function($event){
			$this->route('request.complete', $event);
		});
	}
	
	// dispatch based on filter
	final private function route($event_name, ProxyEvent $event){
		$url = $event['request']->getUri();
		
		// url filter provided and current request url does not match it
		if($this->url_pattern){
			if(starts_with($this->url_pattern, '/') && preg_match($this->url_pattern, $url) !== 1){
				return;
			} else if(stripos($url, $this->url_pattern) === false){
				return;
			}
		}
		
		switch($event_name){
			
			case 'request.before_send':
				$this->onBeforeRequest($event);
			break;
			
			case 'request.sent': 
				$this->onHeadersReceived($event);
			break;
			
			case 'curl.callback.write':
				$this->onCurlWrite($event);
			break;
			
			case 'request.complete':
				$this->onCompleted($event);
			break;
		}
	}
}

?>
