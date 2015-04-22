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
	private $output_buffer_types = array('text/html', 'text/plain', 'text/css', 'text/javascript', 'application/x-javascript', 'application/javascript');
	private $output_buffer = '';
	private $stream = false;
	
	public function __construct(){
		$this->dispatcher = new EventDispatcher();
	}
	
	private function header_callback($ch, $headers){
	
		$parts = explode(":", $headers, 2);
		
		// extract status code
		if(preg_match('/HTTP\/1.\d+ (\d+)/', $headers, $matches)){
		
			$this->response->setStatusCode($matches[1]);
		
		} else if(count($parts) == 2){
			
			$name = strtolower($parts[0]);
			$value = trim($parts[1]);
			
			// this must be a header: value line
			$this->response->headers->set($name, $value, false);
			
		} else {
		
			// this is the end of headers - last line is always empty
			// notify the dispatcher about this
			$this->dispatcher->dispatch('response.headers', $this->generateEvent());
			
			// what content type are we dealing with here? can be empty
			$content_type = $this->response->headers->get('content-type');
			$content_type = clean_content_type($content_type);
			
			// output immediately as it's being streamed or buffer everything until the end?
			if($content_type && !in_array($content_type, $this->output_buffer_types)){

				$this->stream = true;
				$this->response->sendHeaders();
			}
		}
		
		return strlen($headers);
	}
	
	/*
	private function read_callback($ch, $handle, $max){
	
	
		$data = fread($handle, $max);
		$len = strlen($data);

		return $data;
		
		exit;
		
	}
	*/
	
	private function write_callback($ch, $str){
	
		$len = strlen($str);
		
		// if this is a streaming response then output buffer immediately
		if($this->stream){
			echo $str;
			flush();
		} else {
			$this->output_buffer .= $str;
		}
		
		return $len;
	}
	
	private function generateEvent(){
		return new FilterEvent($this->request, $this->response);
	}
	
	public function getEventDispatcher(){
		return $this->dispatcher;
	}
	
	private function convertRequest(){
	
	}
	
	public function execute(Request $request){
	
		$this->request = $request;
		$this->response = new Response();
		
		$options = array(
			CURLOPT_CONNECTTIMEOUT 	=> 10,
			CURLOPT_TIMEOUT 		=> 0,
			
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
		global $config;
		$config_options = $config->get('curl', array());
		
		$options = array_merge_custom($options, $config_options);
		
		$options[CURLOPT_HEADERFUNCTION] = array($this, 'header_callback');
		$options[CURLOPT_WRITEFUNCTION] = array($this, 'write_callback');
		
		//$options[CURLOPT_READFUNCTION] = array($this, 'read_callback');
		
		// modify request further
		$this->dispatcher->dispatch('request.before', $this->generateEvent());

		$headers = $this->request->headers->all();
		
		$real = array();
		
		foreach($headers as $name => $value){
		
			if(is_array($value)){
				$value = implode("; ", $value);
			}
			
			$real[] = $name.': '.$value;
		}
		
		$options[CURLOPT_HTTPHEADER] = $real;
		
		if($this->request->getMethod() == 'POST'){
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = http_build_query($this->request->request->all());
		}
		
		// why not just use request->getUri? because that would rearrange the query params alphabetically which some sites don't like
		$options[CURLOPT_URL] = $request->getScheme().'://'.$request->getHttpHost().$request->getRequestUri();//$this->request->getUri();
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		
		// fetch the status - (at) is here to ignore the errors that come from exceptions within header/body read
		$result = @curl_exec($ch);
		
		if($result){
		
			// we have output waiting in the buffer?
			if(!$this->stream){
			
				$this->response->setContent($this->output_buffer);
				
				$this->dispatcher->dispatch('response.body', $this->generateEvent());
				
				return $this->response;
			}
			
			// if we're streaming then send empty response back
			return new Response();
		}
		
		// there must have been an error if at this point
		$error = sprintf('(%d) %s', curl_errno($ch), curl_error($ch));
		
		throw new ProxyException($error);
	}
}

?>