<?php

require("vendor/autoload.php");

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

require("config.php");
$config = new ParameterBag($config);

require("functions.php");
require("Proxy.php");
require("FilterEvent.php");

require("plugins/AbstractPlugin.php");


// constants to be used throughout
define('PROXY_START', microtime(true));
define('PROXY_VERSION', '1.01');

define('SCRIPT_BASE', (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
define('SCRIPT_DIR', pathinfo(SCRIPT_BASE, PATHINFO_DIRNAME).'/');

//var_dump(SCRIPT_DIR);

// form submit in progress...
if(isset($_POST['url'])){
	
	$url = $_POST['url'];
	$url = add_http($url);
	
	header("HTTP/1.1 302 Found");
	header('Location: '.SCRIPT_BASE.'?q='.encrypt_url($url));
	exit;
	
} else if(!isset($_GET['q'])){

	// must be at homepage - should we be here?
	if($config->has('index_redirect')){
		
		// redirect to...
		header("HTTP/1.1 301 Moved Permanently"); 
		header("Location: ".$config->get('index_redirect'));
		
	} else {
		echo render_template("index", array('script_base' => SCRIPT_BASE, 'version' => PROXY_VERSION));
	}
	
	exit;
}


// get real URL
$url = decrypt_url($_GET['q']);

define('URL', $url);

$request = prepare_from_globals($url);


$proxy = new Proxy();


// load plugins
if($config->has('plugins')){

	foreach($config->get('plugins') as $plugin){
	
		$plugin_class = $plugin.'Plugin';
		
		require_once('plugins/'.$plugin_class.'.php');
		
		$proxy->getEventDispatcher()->addSubscriber(new $plugin_class());
	}
}


try {

	$response = $proxy->execute($request);
	
	// if that was a streaming response, then everything was already sent so response will be empty and nothing actually gets sent here
	$response->send();
	
} catch (Exception $ex){

	// if the site is on server2.proxy.com then you may wish to redirect it back to proxy.com
	if($config->has("error_redirect")){
	
		$url = render_string($config->get("error_redirect"), array(
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