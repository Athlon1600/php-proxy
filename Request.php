<?php

use Symfony\Component\HttpFoundation\ParameterBag;

class Request {

	private $method;
	private $url;
	
	public $headers;
	
	public function __construct($method, $url){
		$this->setMethod($method);
		$this->setUrl($url);
		
		$this->headers = new ParameterBag();
	}
	
	public function setMethod($method){
		$this->method = strtoupper($method);
	}
	
	public function setUrl($url){
		$this->url = $url;
	}
	
	public function getUrl(){
		return $this->url;
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