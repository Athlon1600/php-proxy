<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Event;

class FilterEvent extends Event {

	private $request;
	private $response;
	
	public function __construct(Request $request, Response $response){
		$this->request = $request;
		$this->response = $response;
	}
	
	public function getRequest(){
		return $this->request;
	}
	
	public function getResponse(){
		return $this->response;
	}
	
}

?>