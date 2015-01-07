<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;

class Proxy {

	private $request;
	private $response;
	private $dispatcher;
	
	// stream: Set to true to stream a response body rather than download it all up front
	private $stream = false;
	
	public function __construct(Request $request){
		
		$this->request = $request;
		$this->response = new Response();
		
		$this->dispatcher = new EventDispatcher();
	}

	private $mime_types = array(
		'text/html' => 'html',
		'text/plain' => 'html',
		'text/css' => 'css',
		'text/javascript' => 'js',
		'application/x-javascript' => 'js',
		'application/javascript' => 'js'
	);
	
	private function header_callback($ch, $headers){
	
		$parts = explode(":", $headers, 2);
		
		if(count($parts) == 2){
			
			$name = strtolower($parts[0]);
			$value = trim($parts[1]);
			
			// set it up!
			$this->response->headers->set($name, $value, false);
			
		} else if(preg_match('/HTTP\/1.\d+ (\d+)/', $headers, $matches)){
		
			$this->response->setStatusCode($matches[1]);
			
		} else {
		
			// do this
			$this->dispatcher->dispatch('response.headers', $this->generateEvent());
			
			// end of headers - last line is always empty
			
			// what content type are we dealing with here?
			$content_type = $this->response->headers->get('content-type');
			
			// extract just the part that we want
			$pos = strpos($content_type, ';');
			$content_type = substr($content_type, 0, $pos ? $pos : 999);
			
			// output immediately as it's being streamed or buffer everything until the end?
			if(!isset($this->mime_types[$content_type])){

				$this->stream = true;
				$this->response->sendHeaders();
			}
		}
		
		return strlen($headers);
	}
	
	private $output = '';
	
	private function write_callback($ch, $str){
	
		$len = strlen($str);
		
		if($this->stream){
			echo $str;
			flush();
		} else {
			$this->output .= $str;
		}
		
		return $len;
	}
	
	private function generateEvent(){
		return new FilterEvent($this->request, $this->response);
	}
	
	public function addPlugin(EventSubscriberInterface $plugin){
		$this->dispatcher->addSubscriber($plugin);
	}
	
	public function execute($url){
	
		$options = array(
			CURLOPT_CONNECTTIMEOUT 	=> 5,
			CURLOPT_TIMEOUT 		=> 0,
			
			// don't return anything - we have other functions for that
			CURLOPT_RETURNTRANSFER	=> false,
			CURLOPT_HEADER			=> false,
			
			// don't bother with ssl
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_SSL_VERIFYHOST	=> false,
			
			// we will take care of redirects
			CURLOPT_FOLLOWLOCATION	=> false,
			CURLOPT_MAXREDIRS		=> 5,
			CURLOPT_AUTOREFERER		=> false
		);
		
		$options[CURLOPT_HEADERFUNCTION] = array($this, 'header_callback');
		$options[CURLOPT_WRITEFUNCTION] = array($this, 'write_callback');
		
		// modify request further
		$this->dispatcher->dispatch('request.before', $this->generateEvent());

		
		$headers = $this->request->headers->all();

		$real = array();
		
		foreach($headers as $name => $value){
			$value = $value[0];
			$real[] = $name.': '.$value;
		}
		
		$options[CURLOPT_HTTPHEADER] = $real;
		
		if($this->request->getMethod() == 'POST'){
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $this->request->getContent();
		}
		
		$options[CURLOPT_URL] = $url;
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		
		// fetch the status
		$result = curl_exec($ch);
		
		if($result){
		
			// we have output waiting in the buffer?
			if(!$this->stream){
			
				$this->response->setContent($this->output);
				
				$this->dispatcher->dispatch('response.body', $this->generateEvent());
				
				return $this->response;
			}
			
			// if we're streaming then send empty response back
			return new Response();
		}
		
		// set error
		$error = sprintf('(%d) %s', curl_errno($ch), curl_error($ch));
		
		throw new ProxyException($error);
	}
}

?>