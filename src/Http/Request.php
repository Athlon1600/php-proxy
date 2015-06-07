<?php

namespace Proxy\Http;

use Proxy\Http\ParamStore;

class Request {

	private $method;
	private $url;
	
	private $protocol_version = '1.1';
	
	// Custom attributes to go with each Request instance - has nothing to do with HTTP
	public $params; // parameters
	
	// Query string for URL queryParams
	//public $query;
	
	// HTTP headers for this request - all in lowercase
	public $headers;
	
	// Here we store cookies for that request
	//public $cookies;
	
	// Collection of POST fields to be submitted
	public $post;
	
	// Files to be uploaded with POST
	public $files;
	
	// Raw POST data that will be sent
	private $body = null;
	
	public function __construct($method, $url, $headers = array(), $body = null){

		$this->params = new ParamStore();
		$this->headers = new ParamStore();
		
		// POST data
		$this->post = new ParamStore();
		$this->files = new ParamStore();
		
		$this->setMethod($method);
		$this->setUrl($url);
		$this->setBody($body);
		
		// make the request ready to be sent right from the start - prepare must be called manually from this point on if you ever add post or file data
		$this->prepare();
	}
	
	// add content-type, content-length, transfer-encoding, and expect headers
	public function prepare(){
	
		/*
		Any HTTP/1.1 message containing an entity-body SHOULD include a Content-Type header field defining the media type of that body. 
		http://www.w3.org/Protocols/rfc2616/rfc2616-sec7.html#sec7.2.1
		*/
		
		// Must be a multipart request
		if($this->files->all()){
		
			$boundary = '-----'.md5(microtime().rand());
		
			$this->body = Request::buildPostBody($this->post->all(), $this->files->all());
			$this->headers->set('content-type', 'multipart/form-data; boundary=bbb');
		
		} else if($this->post->all()){
			
			$this->body = http_build_query($this->post->all());
			$this->headers->set('content-type', 'application/x-www-form-urlencoded');
			
		} else if(!$this->headers->has('content-type')){
		
			// detect content-type on our own
		}
		
		/*
		The transfer-length of a message is the length of the message-body as it appears in the message; that is, after any transfer-codings have been applied.
		http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.4
		*/
		
		$len = strlen($this->body);
		
		if($len == 0){
			$this->headers->remove('content-length');
		} else {
			$this->headers->set('content-length', $len);
		}
	}
	
	public function __toString(){
		$str = $this->getMethod().' '.$this->getUrl().' HTTP/'.$this->getProtocolVersion()."\r\n";
		return $str.$this->getRawHeaders()."\r\n\r\n".$this->getRawBody();
	}
	
	public function setMethod($method){
		$this->method = strtoupper($method);
	}
	
	public function getMethod(){
		return $this->method;
	}
	
	public function setUrl($url){
		$this->url = $url;
		
		$this->headers->set('host', parse_url($url, PHP_URL_HOST));
	}
	
	public function getRawHeaders(){
		
		$result = array();
		
		$headers = $this->headers->all();
		
		// Sort headers by name
		ksort($headers);
		
		// Turn this into name=value pairs
		foreach($headers as $name => $values){
			$name = implode('-', array_map('ucfirst', explode('-', $name)));
			$result[] = sprintf("%s: %s", $name, $values);
		}
		
		return implode("\r\n", $result);
	}
	
	public function getUrl(){
		return $this->url;
	}
	
	public function getUri(){
		return call_user_func_array(array($this, "getUrl"), func_get_args());
	}
	
	public function setProtocolVersion($version){
		$this->protocol_version = $version;
	}
	
	public function getProtocolVersion(){
		return $this->protocol_version;
	}
	
	// Setting body will delete/overwrite all the parameters currently stored in post and files
	public function setBody($body, $content_type = false){
	
		// clear old body data
		$this->post->clear();
		$this->files->clear();
		
		// is this form data?
		if(is_array($body)){
			$body = http_build_query($body);
		}
		
		$this->body = $body;
		
		// plain text should be: text/plain; charset=UTF-8
		if($content_type){
			$this->headers->set('content-type', $content_type);
		}
		
		// do it!
		$this->prepare();
	}
	
	// can be $_POST and $_FILES
	public static function buildPostBody($fields, $files, $boundary = null){
	
		// the reason BODY part is not included in sprintf pattern is because of limits
		$part_field = "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n";
		$part_file = "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n";

		// each part should be preceeded by this line
		if(!$boundary){
			$boundary = '-----'.md5(microtime().rand());
		}
		
		$body = '';
		
		foreach($fields as $name => $value){
			$body .= sprintf($part_field, $boundary, $name, $value);
			$body .= "{$value}\r\n";
		}
		
		// data better have [name, tmp_name, and optional type]
		foreach($files as $name => $values){
			
			// There must be no error http://php.net/manual/en/features.file-upload.errors.php
			if(!$values['tmp_name'] || $values['error'] !== 0 || !is_readable($values['tmp_name'])){
				continue;
			}
			
			$body .= sprintf($part_file, $boundary, $name, $values['name'], $values['type']);
			$body .= file_get_contents($values['tmp_name']);
			$body .= "\r\n";
		}
		
		$body .= "--{$boundary}--\r\n\r\n";
		
		return $body;
	}
	
	private function getBodyContentType(){
		
		// http://www.w3.org/Protocols/rfc1341/4_Content-Type.html
		
		// If the media type remains unknown, the recipient SHOULD treat it as type "application/octet-stream". 
		$content_type = 'application/octet-stream';
		
		if(preg_match('/^{\s*"[^"]+"\s*:/', $this->body)){
			$content_type = 'application/json';
		} else if(preg_match('/^(?:<\?xml[^?>]+\?>)\s*<[^>]+>/i', $this->body)){
			$content_type = 'application/xml';
		} else if(preg_match('/^[a-zA-Z0-9_.~-]+=[^&]*&/', $this->body)){
			$content_type = 'application/x-www-form-urlencoded';
		}
		
		return $content_type;
	}
	
	// Returns a parsed version of the body
	public function getBody(){
	
		// what is the content type?
		$content_type = $this->headers->get('content-type', '');
		
		switch($content_type){
			case 'application/x-www-form-urlencoded':
				$result = array();
				mb_parse_str($this->body, $result);
				return $result;
			case 'application/json':
				return json_decode($this->body);
			case 'text/xml':
			case 'application/xml':
			case 'application/x-xml':
				return simplexml_load_string($this->body);
		}
		
		return null;
	}
	
	// Returns raw body string exactly as it appears in the HTTP request
	public function getRawBody(){	
		return $this->body;
	}
	
	public static function createFromGlobals(){
	
		$method = $_SERVER['REQUEST_METHOD'];
		$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http';
		
		$url = $scheme.'://'. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		
		$request = new Request($method, $url);
		
		// fill in headers
		foreach($_SERVER as $name => $value){
		
			if(strpos($name, 'HTTP_') === 0){
			
				$name = substr($name, 5);
				$name = str_replace('_', ' ', $name);
				$name = ucwords(strtolower($name));
				$name = str_replace(' ', '-', $name);
				
				$request->headers->set($name, $value);
			}
		}
		
		// for extra convenience
		//$request->params->set('user-ip', $_SERVER['REMOTE_ADDR']);
		
		// will be empty if content-type is multipart
		$input = file_get_contents("php://input");
		
		if($input){
			$request->setBody($input);
		} else if(count($_FILES) > 0 || count($_POST) > 0){
			$request->post->replace($_POST);
			$request->files->replace($_FILES);
		}
		
		// for extra convenience
		$request->prepare();
		
		return $request;
	}
}


?>