<?php

require("vendor/autoload.php");

define('PROXY_START', microtime(true));
define('SCRIPT_BASE', (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
define('SCRIPT_DIR', pathinfo(SCRIPT_BASE, PATHINFO_DIRNAME).'/');

require("src/helpers.php");
require("src/Config.php");
require("src/Proxy.php");
require("src/Event/FilterEvent.php");

require("src/Plugin/AbstractPlugin.php");

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\FilterEvent;
use Proxy\Config;
use Proxy\Proxy;


// load config...
Config::load('./config.php');

// form submit in progress...
if(isset($_POST['url'])){
	
	$url = $_POST['url'];
	$url = add_http($url);
	
	header("HTTP/1.1 302 Found");
	header('Location: '.SCRIPT_BASE.'?q='.encrypt_url($url));
	exit;
	
} else if(!isset($_GET['q'])){

	// must be at homepage - should we redirect somewhere else?
	if(Config::get('index_redirect')){
		
		// redirect to...
		header("HTTP/1.1 301 Moved Permanently"); 
		header("Location: ".Config::get('index_redirect'));
		
	} else {
		echo render_template("index", array('script_base' => SCRIPT_BASE, 'version' => PROXY_VERSION));
	}
	
	exit;
}


// get real URL
$url = decrypt_url($_GET['q']);

define('URL', $url);


$request = request_from_globals($url);


$proxy = new Proxy();


// load plugins
foreach(Config::get('plugins', array()) as $plugin){

	$plugin_class = $plugin.'Plugin';
	
	require_once('src/Plugin/'.$plugin_class.'.php');
	
	$plugin_class = '\\Proxy\\Plugin\\'.$plugin_class;
	
	$proxy->getEventDispatcher()->addSubscriber(new $plugin_class());
}

// provide URL form
$proxy->getEventDispatcher()->addListener('request.complete', function($event){

	$request = $event->getRequest();
	$response = $event->getResponse();
	
	$url = $request->getUri();
	
	// we attach url_form only if this is a html response
	if(!is_html($response->headers->get('content-type'))){
		return;
	}
	
	$url_form = render_template("url_form", array(
		'url' => $url,
		'script_base' => SCRIPT_BASE
	));
	
	$output = $response->getContent();
	
	// does the html page contain <body> tag, if so insert our form right after <body> tag starts
	$output = preg_replace('@<body.*?>@is', '$0'.PHP_EOL.$url_form, $output, 1, $count);
	
	// <body> tag was not found, just put the form at the top of the page
	if($count == 0){
		$output = $url_form.$output;
	}
	
	$response->setContent($output);
});


try {

	$response = $proxy->execute($request);
	
	// if that was a streaming response, then everything was already sent so response will be empty and nothing actually will be sent here
	$response->send();
	
} catch (Exception $ex){

	// if the site is on server2.proxy.com then you may wish to redirect it back to proxy.com
	if(Config::get("error_redirect")){
	
		$url = render_string(Config::get("error_redirect"), array(
			'error_msg' => rawurlencode($ex->getMessage())
		));
		
		header("HTTP/1.1 302 Found");
		header("Location: {$url}");
		
	} else {
	
		echo render_template("index", array(
			'url' => $url,
			'script_base' => SCRIPT_BASE,
			'error_msg' => $ex->getMessage(),
			'version' => PROXY_VERSION
		));
		
	}
}


?>