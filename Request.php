<?php

use Symfony\Component\HttpFoundation\ParameterBag;

class Request {

	private $method;
	private $url;
	
	private $server;
	
	public $headers;
	public $data;
	
	public function __construct($method, $url){
	
		$this->headers = new ParameterBag();
		$this->data = array();
	
		$this->setMethod($method);
		$this->setUrl($url);
		
		$this->server = $_SERVER;
	}
	
	public function setMethod($method){
		$this->method = strtoupper($method);
	}
	
	public function getClientIp(){
		return $this->server['REMOTE_ADDR'];
	}
	
	public function setUrl($url){
		$this->url = $url;
		
		// update Host header
		$this->headers->set('Host', parse_url($url, PHP_URL_HOST));
	}
	
	public function getUrl(){

		$qs = '';
		
		if($this->method == 'GET'){
			
			// overwrite or not?
			if(strpos($this->url, '?') !== false){
				$qs = '&'.http_build_query($this->data);
			} else {
				$qs = '?'.http_build_query($this->data);
			}
		}
		
		return $this->url.$qs;
	}
	
	public function matchesUrl($url){
		return strpos($this->url, $url) !== false;
	}
	
	public function setHeader($name, $value){
		$this->headers->set($name, $value);
	}
	
	public static function fromGlobals(){
	
		$method = $_SERVER['REQUEST_METHOD'];
		$url = 'http://'. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		
		$request = new Request($method, $url);
		
		// fill in headers
		foreach($_SERVER as $name => $value){
		
			if(strpos($name, 'HTTP_') === 0){

				$name = substr($name, 5);
				$name = str_replace('_', ' ', $name);
				$name = ucwords(strtolower($name));
				$name = str_replace(' ', '-', $name);
				
				$request->setHeader($name, $value);
			}
		}
	
		return $request;
	}

}

?>