<?php

require("vendor/autoload.php");

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require("global.php");
require("Proxy.php");

require("exceptions/ProxyException.php");
require("FilterEvent.php");

// load all plugins at once
foreach (glob("plugins/*.php") as $filename){
    require($filename);
}

// constants to be used throughout
define('SCRIPT_BASE', (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
define('PLAYER_URL', '//git.proxynova.com/php-proxy/flowplayer/flowplayer-3.2.18.swf');



// form submit in progress...
if(isset($_POST['url'])){
	
	$url = $_POST['url'];
	$url = add_http($url);
	
	header("HTTP/1.1 302 Found");
	header('Location: '.SCRIPT_BASE.'?q='.encrypt_url($url));
	exit;
	
} else if(!isset($_GET['q'])){

	// must be at homepage!
	echo render_template("index", array('script_base' => SCRIPT_BASE));
	exit;
}

$url = decrypt_url($_GET['q']);

define('URL', $url);

// must override URL
$request = Request::createFromGlobals();
$request = request_set_url($request, $url);

$proxy = new Proxy($request);



$proxy->addPlugin(new HeaderPlugin());
$proxy->addPlugin(new CookiePlugin());
//$proxy->addPlugin(new ProxifyPlugin());
$proxy->addPlugin(new YoutubePlugin());


try {

	$response = $proxy->execute($url);
	
	// are we streaming?
	if(!headers_sent()){
	
		$response->sendHeaders();
		
		echo render_template("url_form", array(
			'content' => $response->getContent(),
			'url' => $url,
			'script_base' => SCRIPT_BASE
		));
	}
	
} catch (Exception $ex){

	echo render_template("index", array('script_base' => SCRIPT_BASE, 'error_msg' => $ex->getMessage()));
}




?>