php-proxy
=========

Proxy script built on PHP, Symfony and cURL.
This library borrows ideas from Glype, Jenssegers proxy, and Guzzle.

PHP-Proxy Web Application
-------

If you're looking for a **project** version of this script that functions as a Web Application similar to Glype, then visit
[**php-proxy-app**](https://github.com/Athlon1600/php-proxy-app)

See this php-proxy in action:
<a href="https://unblockvideos.com/" target="_blank">UnblockVideos.com</a>

Installation
-------

Install it using [Composer](http://getcomposer.org):

```bash
composer require athlon1600/php-proxy
```

Example
--------

```php
require('vendor/autoload.php');

use Proxy\Http\Request;
use Proxy\Proxy;

$request = Request::createFromGlobals();

$proxy = new Proxy();

$proxy->getEventDispatcher()->addListener('request.before_send', function($event){

	$event['request']->headers->set('X-Forwarded-For', 'php-proxy');
	
});

$proxy->getEventDispatcher()->addListener('request.sent', function($event){

	if($event['response']->getStatusCode() != 200){
		die("Bad status code!");
	}
  
});

$proxy->getEventDispatcher()->addListener('request.complete', function($event){

	$content = $event['response']->getContent();
	$content .= '<!-- via php-proxy -->';
	
	$event['response']->setContent($content);
	
});

$response = $proxy->forward($request, "http://www.yahoo.com");

// send the response back to the client
$response->send();

```

