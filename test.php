<?php

require('vendor/autoload.php');

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


echo "<pre>";

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

use GuzzleHttp\Client;


require("Request.php");




$request = Request::fromGlobals();


$request->data = 'gdfgdfg';

$request->data['gdfgdf'] ='gdfgdfg';



$request->data['two'] = '2';

$request->data['three'] = '3';



var_dump($request->getUrl());


exit;




echo "POST";
print_r($_POST);

echo "GET";
//var_dump(file_get_contents("php://input"));

print_r($_GET);



$request = Request::create('https://www.php-proxy.com/php-proxy/test.php', 'POST', array('page' => 100));

function request_set_get(Request $request){

	// get post vars
	$post = $request->request->all();
	
	$request->server->set('REQUEST_METHOD', 'GET');
	$request->server->set('QUERY_STRING', http_build_query($post));
	
	// get post attributes cookie files server
	$request = $request->duplicate($post, array());
	
	return $request;
}

function request_set_post(Request $request){

	$get = $request->query->all();
	
	$request->server->set('REQUEST_METHOD', 'POST');
	$request->server->remove('QUERY_STRING');
	
	$request = $request->duplicate(array(), $get);
	
	return $request;
}

function request_change_url(Request $request){


	return Request::create("https://github.com/symfony/HttpFoundation/blob/master/Request.php",
	
	$request->getMethod() == 'POST' ? $request->request->all() : $request->query->all(), 
	
	$request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());
 
 
	$request->server->remove('HTTPS');
	
	
	//$request->server->remove('https');
	
	$request->server->set('SERVER_PORT', 80);
	
	$request->headers->set('Host', 'ayahoo.com');
	
	$request->server->set('HTTP_HOST', 'yahoo.com');
	$request->server->set('SERVER_NAME', 'yahoo.com');
	$request->server->set('SERVER_ADDR', 'yahoo.com');

	return $request;
}



$request = Request::create('http://google.com', 'POST', array('one' => 1, 'two' => 2222));

var_dump($request->getUri());
var_dump($request->getMethod());



$request = request_change_url($request);


var_dump($request->getUri());
var_dump($request->getMethod());



?>