<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

// do not buffer certain responses... echo contents immediately, and exit when done
class StreamPlugin extends AbstractPlugin {

	// stream: Set to true to stream a response body rather than download it all up front
	private $output_buffer_types = array('text/html', 'text/plain', 'text/css', 'text/javascript', 'application/x-javascript', 'application/javascript');
	private $stream = false;
	
	// we stream response as it is received if its content length exceeds 5 megabytes
	private $max_content_length = 5000000;
	
	public function onHeadersReceived(ProxyEvent $event){
	
		// what content type are we dealing with here? can be empty
		$content_type = $event['response']->headers->get('content-type');
		$content_type = clean_content_type($content_type);
		
		// how big of data can we expect?
		$content_length = $event['response']->headers->get('content-length');
		
		// we stream if content is not of "text" content-type or if its size exceeds 5 megabytes
		if(!in_array($content_type, $this->output_buffer_types) || $content_length > $this->max_content_length){
		
			$this->stream = true;
			$event['response']->sendHeaders();
			
			// it is of no use for us to buffer the data since we're sending it out immediately, but sometimes we must do both, hence the parameter
			if(!$event['request']->params->has('force_buffering')){
				$event['proxy']->setOutputBuffering(false);
			}
		}
	}
	
	public function onCurlWrite(ProxyEvent $event){
	
		if($this->stream){
			echo $event['data'];
			flush();
		}
	}
	
	public function onCompleted(ProxyEvent $event){
	
		// if this was a streaming response then exit the script immediately since we do not wish to process it any futher
		if($this->stream){
			exit;
		}
	}
}

?>