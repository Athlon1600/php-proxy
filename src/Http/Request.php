<?php

namespace Proxy\Http;

use Proxy\Http\ParamStore;

class Request {

	private $method;
	private $url;
	
	private $protocol_version = '1.1';
	
	// Custom attributes to go with each Request instance - has nothing to do with HTTP
	public $params; // parameters
	
	// HTTP headers for this request - all in lowercase
	public $headers;
	
	// Here we store cookies for that request
	//public $cookies;
	
	// Collection of POST fields to be submitted
	public $post;
	
	// Query string parameters for URL
	public $get;
	
	// Files to be uploaded with POST
	public $files;
	
	// User set body contents
	private $body = null;
	
	// Library generated body that is regenerated through prepare method
	private $prepared_body = null;
	
	public function __construct($method, $url, $headers = array(), $body = null){

		$this->params = new ParamStore();
		$this->headers = new ParamStore();
		
		// http params
		$this->post = new ParamStore(null, true);
		$this->get = new ParamStore(null, true);
		
		$this->files = new ParamStore(null, true);
		
		$this->setMethod($method);		
		$this->setUrl($url);
		$this->setBody($body);
		
		// make the request ready to be sent right from the start - prepare must be called manually from this point on if you ever add post or file parameters
		$this->prepare();
	}
	
	/*
		Does multiple things
		- regenerate content body based on $post and $files parameters
		- set content-type, content-length headers
		- set transfer-encoding, expect headers
	*/
	public function prepare(){
	
		/*
		Any HTTP/1.1 message containing an entity-body SHOULD include a Content-Type header field defining the media type of that body. 
		http://www.w3.org/Protocols/rfc2616/rfc2616-sec7.html#sec7.2.1
		*/
		
		// Must be a multipart request
		if($this->files->all()){
		
			$boundary = self::generateBoundary();
			
			$this->prepared_body = Request::buildPostBody($this->post->all(), $this->files->all(), $boundary);
			$this->headers->set('content-type', 'multipart/form-data; boundary='.$boundary);
		
		} else if($this->post->all()){
			
			$this->prepared_body = http_build_query($this->post->all());
			$this->headers->set('content-type', 'application/x-www-form-urlencoded');
			
		} else {
		
			$this->headers->set('content-type', $this->detectContentType($this->body));
			$this->prepared_body = $this->body;
		}
		
		/*
		The transfer-length of a message is the length of the message-body as it appears in the message; that is, after any transfer-codings have been applied.
		http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.4
		*/
		
		$len = strlen($this->prepared_body);
		
		if($len > 0){
			$this->headers->set('content-length', $len);
		} else {
			$this->headers->remove('content-length');
			$this->headers->remove('content-type');
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
	
	// this was no longer working --- https://github.com/guzzle/psr7/blob/master/src/functions.php
	public static function parseQuery($query){
		$result = array();
		parse_str($query, $result);
		
		return $result;
	}
	
	public function setUrl($url){
		// remove hashtag - preg_replace so we don't have to check for its existence first - is it possible preserving hashtag?
		$url = preg_replace('/#.*/', '', $url);
		
		// check if url has any query parameters
		$query = parse_url($url, PHP_URL_QUERY);
		
		// remove it and add the query params to get collection
		if($query){
			//$url = str_replace('?'.$query, '', $url);
			$url = preg_replace('/\?.*/', '', $url);
			
			$result = self::parseQuery($query);
			$this->get->replace($result);
		}
		
		// url without query params - those will be appended later
		$this->url = $url;
		$this->headers->set('host', parse_url($url, PHP_URL_HOST));
	}
	
	public function getRawHeaders(){
		
		$result = array();
		
		$headers = $this->headers->all();
		
		// Sort headers by name
		//ksort($headers);
		
		// Turn this into name=value pairs
		foreach($headers as $name => $values){
		
			// could be an array if multiple headers are sent with the same name?
			foreach( (array)$values as $value){
				$name = implode('-', array_map('ucfirst', explode('-', $name)));
				$result[] = sprintf("%s: %s", $name, $value);
			}
		}
		
		return implode("\r\n", $result);
	}
	
	public function getUrl(){
		
		// does this URL have any query parameters?
		if($this->get->all()){
			return $this->url.'?'.http_build_query($this->get->all());
		}
		
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
	
	// Set raw contents of the body
	// this will clear all the values currently stored in POST and FILES
	// will be ignored during PREPARE if post or files contain any values
	public function setBody($body, $content_type = false){
	
		// clear old body data
		$this->post->clear();
		$this->files->clear();
		
		// is this form data?
		if(is_array($body)){
			$body = http_build_query($body);
		}
		
		$this->body = (string)$body;
		
		// plain text should be: text/plain; charset=UTF-8
		if($content_type){
			$this->headers->set('content-type', $content_type);
		}
		
		// do it!
		$this->prepare();
	}
	
	private static function generateBoundary(){
		return '-----'.md5(microtime().rand());
	}
	
	// can be $_POST and $_FILES
	public static function buildPostBody($fields, $files, $boundary = null){
	
		// the reason BODY part is not included in sprintf pattern is because of limits
		$part_field = "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n";
		$part_file = "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n";

		// each part should be preceeded by this line
		if(!$boundary){
			$boundary = self::generateBoundary();
		}
		
		$body = '';
		
		foreach($fields as $name => $value){
			$body .= sprintf($part_field, $boundary, $name, $value);
			$body .= "{$value}\r\n";
		}
		
		// data better have [name, tmp_name, and optional type]
		foreach($files as $name => $values) {
			// Multiple files can be uploaded using different name for input.
			// See http://php.net/manual/en/features.file-upload.multiple.php
			if (!is_array($values['tmp_name'])) {
				$multiValues = array_map(function ($a) {
					return (array)$a;
				}, $values);
				$fieldName = $name;
			} else {
				$multiValues = $values;
				$fieldName = "{$name}[]";
			}

			foreach (array_keys($multiValues['tmp_name']) as $key) {

				// There must be no error http://php.net/manual/en/features.file-upload.errors.php
				if (!$multiValues['tmp_name'][$key] || $multiValues['error'][$key] !== 0 || !is_readable($multiValues['tmp_name'][$key])) {
					continue;
				}

				$body .= sprintf($part_file, $boundary, $fieldName, $multiValues['name'][$key], $multiValues['type'][$key]);
				$body .= file_get_contents($multiValues['tmp_name'][$key]);
				$body .= "\r\n";
			}
		}
		$body .= "--{$boundary}--\r\n\r\n";
		
		return $body;
	}
	
	private function detectContentType($data){
		
		// http://www.w3.org/Protocols/rfc1341/4_Content-Type.html
		
		// If the media type remains unknown, the recipient SHOULD treat it as type "application/octet-stream". 
		$content_type = 'application/octet-stream';
		
		if(preg_match('/^{\s*"[^"]+"\s*:/', $data)){
			$content_type = 'application/json';
		} else if(preg_match('/^(?:<\?xml[^?>]+\?>)\s*<[^>]+>/i', $data)){
			$content_type = 'application/xml';
		} else if(preg_match('/^[a-zA-Z0-9_.~-]+=[^&]*&/', $data)){
			$content_type = 'application/x-www-form-urlencoded';
		}
		
		return $content_type;
	}
	
	// Returns a parsed version of the body
	/*
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
	*/
	
	// Returns raw body string exactly as it appears in the HTTP request
	public function getRawBody(){
		return $this->prepared_body;
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
		
		if(count($_FILES) > 0){
			$request->post->replace($_POST);
			$request->files->replace($_FILES);
		} else if(count($_POST) > 0){
			$request->post->replace($_POST);
		} else {
			$request->setBody($input);
		}
		
		// for extra convenience
		$request->prepare();
		
		return $request;
	}
}


?>