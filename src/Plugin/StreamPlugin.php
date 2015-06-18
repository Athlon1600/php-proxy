<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class StreamPlugin extends AbstractPlugin {

	// stream: Set to true to stream a response body rather than download it all up front
	private $output_buffer_types = array('text/html', 'text/plain', 'text/css', 'text/javascript', 'application/x-javascript', 'application/javascript');
	private $stream = false;
	
	public function onHeadersReceived(ProxyEvent $event){
	
		// what content type are we dealing with here? can be empty
		$content_type = $event['response']->headers->get('content-type');
		$content_type = clean_content_type($content_type);
		
		// output immediately as it's being streamed or buffer everything until the end?
		if($content_type && !in_array($content_type, $this->output_buffer_types)){
		
			$this->stream = true;
			$event['response']->sendHeaders();
			
			// Tell proxy not to buffer this
			$event['proxy']->setOutputBuffering(false);
		}
	}
	
	public function onCurlWrite(ProxyEvent $event){
	
		if($this->stream){
			echo $event['data'];
			flush();
		}
	}
	
	// VERY IMPORTANT!!!! Otherwise that huge piece of data from a large video or whatever will be sent through every plugin, 
	// and going through every preg_replace which crashes PHP with "out of memory" errors.
	public function onCompleted(ProxyEvent $event){
	
		// if this was a streaming response then exit immediately
		if($this->stream){
			exit;
		}
	}
}

?>