<?php

namespace Proxy;

use Proxy\Event\ProxyEvent;
use Proxy\Http\Request;
use Proxy\Http\Response;
use Proxy\Config;

class Proxy {
	
	// Proxy script version
	const VERSION = '5.1.0';
	
	private $dispatcher;
	
	private $request;
	private $response;
	
	private $output_buffering = true;
	private $output_buffer = '';
	
	private $status_found = false;
	
	public function __construct(){
		// do nothing for now
	}
	
	public function setOutputBuffering($output_buffering){
		$this->output_buffering = $output_buffering;
	}
	
	private function header_callback($ch, $headers){
		$parts = explode(":", $headers, 2);
		
		// extract status code
		// if using proxy - we ignore this header: HTTP/1.1 200 Connection established
		if(preg_match('/HTTP\/1.\d+ (\d+)/', $headers, $matches) && stripos($headers, '200 Connection established') === false){
			
			$this->response->setStatusCode($matches[1]);
			$this->status_found = true;
		
		} else if(count($parts) == 2){
			
			$name = strtolower($parts[0]);
			$value = trim($parts[1]);
			
			// this must be a header: value line
			$this->response->headers->set($name, $value, false);
			
		} else if($this->status_found){
		
			// this is hacky but until anyone comes up with a better way...
			$event = new ProxyEvent(array('request' => $this->request, 'response' => $this->response, 'proxy' => &$this));
			
			// this is the end of headers - last line is always empty - notify the dispatcher about this
			$this->dispatch('request.sent', $event);
		}
		
		return strlen($headers);
	}
	
	private function write_callback($ch, $str){
	
		$len = strlen($str);
		
		$this->dispatch('curl.callback.write', new ProxyEvent(array(
			'request' => $this->request,
			'data' => $str
		)));
		
		// Do we buffer this piece of data for later output or not?
		if($this->output_buffering){
			$this->output_buffer .= $str;
		}
		
		return $len;
	}
	
	// TODO: move this all into its own Dispatcher class?
	// https://github.com/guzzle/guzzle/blob/5.3/src/Event/Emitter.php
	// https://github.com/laravel/framework/blob/5.0/src/Illuminate/Events/Dispatcher.php#L72
	private $listeners = array();
	
	// Proxy instance itself acts as a dispatcher!
	public function getEventDispatcher(){
		return $this;
	}
	
	public function addListener($event, $callback, $priority = 0){
		$this->listeners[$event][$priority][] = $callback;
	}
	
	public function addSubscriber($subscriber){
		if(method_exists($subscriber, 'subscribe')){
			$subscriber->subscribe($this);
		}
	}
	
	private function dispatch($event_name, $event){
		
		if(isset($this->listeners[$event_name])){
			$temp = (array)$this->listeners[$event_name];
			
			foreach($temp as $priority => $listeners){
				foreach( (array)$listeners as $listener){
					if(is_callable($listener) ){
						$listener($event);
					}
				}
			}
		}
	}
	
	public function forward(Request $request, $url){
	
		// change request URL
		$request->setUrl($url);
		
		// prepare request and response objects
		$this->request = $request;
		$this->response = new Response();
		
		$options = array(
			CURLOPT_CONNECTTIMEOUT 	=> 10,
			CURLOPT_TIMEOUT			=> 0,
			
			// don't return anything - we have other functions for that
			CURLOPT_RETURNTRANSFER	=> false,
			CURLOPT_HEADER			=> false,
			
			// don't bother with ssl
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_SSL_VERIFYHOST	=> false,
			
			// we will take care of redirects
			CURLOPT_FOLLOWLOCATION	=> false,
			CURLOPT_AUTOREFERER		=> false
		);
		
		// this is probably a good place to add custom curl options that way other critical options below would overwrite that
		$config_options = Config::get('curl', array());
		
		$options = array_merge_custom($options, $config_options);
		
		$options[CURLOPT_HEADERFUNCTION] = array($this, 'header_callback');
		$options[CURLOPT_WRITEFUNCTION] = array($this, 'write_callback');
		
		// Notify any listeners that the request is ready to be sent, and this is your last chance to make any modifications.
		$this->dispatch('request.before_send', new ProxyEvent(array(
			'request' => $this->request,
			'response' => $this->response
		)));
		
		// We may not even need to send this request if response is already available somewhere (CachePlugin)
		if($this->request->params->has('request.complete')){
			// do nothing?
		} else {
		
			// any plugin might have changed our URL by this point
			$options[CURLOPT_URL] = $this->request->getUri();
			
			// fill in the rest of cURL options
			$options[CURLOPT_HTTPHEADER] = explode("\r\n", $this->request->getRawHeaders());
			$options[CURLOPT_CUSTOMREQUEST] = $this->request->getMethod();
			$options[CURLOPT_POSTFIELDS] =  $this->request->getRawBody();
			
			$ch = curl_init();
			curl_setopt_array($ch, $options);
			
			// fetch the status - if exception if throw any at callbacks, then the error will be supressed
			$result = @curl_exec($ch);
			
			// there must have been an error if at this point
			if(!$result){
				$error = sprintf('(%d) %s', curl_errno($ch), curl_error($ch));
				throw new \Exception($error);
			}
			
			// we have output waiting in the buffer?
			$this->response->setContent($this->output_buffer);
			
			// saves memory I would assume?
			$this->output_buffer = null;
		}
		
		$this->dispatch('request.complete', new ProxyEvent(array(
			'request' => $this->request,
			'response' => $this->response
		)));
		
		return $this->response;
	}
}

?>
