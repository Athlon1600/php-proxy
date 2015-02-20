<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Event;

class FilterEvent extends Event implements \ArrayAccess {

	protected $data = array();
	
	private $request;
	private $response;
	
	public function __construct(Request $request, Response $response){
		$this->data['request'] = $request;
		$this->data['response'] = $response;
		
		$this->request = $request;
		$this->response = $response;
	}
	
	public function offsetExists($offset){
		return isset($this->data[$offset]);
	}
	
	public function offsetGet($offset){
		return $this->data[$offset];
	}
	
	public function offsetSet($offset, $value){
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset){
		unset($this->data[$offset]);
	}
	
	public function getRequest(){
		return $this->request;
	}
	
	public function getResponse(){
		return $this->response;
	}
	
}

?>