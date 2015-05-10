php-proxy
=========

Proxy script built on PHP, Symfony and cURL.
This library borrows ideas from Glype, Jenssegers proxy, and Guzzle.

Full Working PHP-Proxy
-------

If you're looking for a **project** version of this script that functions similar to Glype, then visit
[php-proxy-script](https://github.com/Athlon1600/php-proxy-script)

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

use Symfony\Component\HttpFoundation\Request;
use Proxy\Proxy;

$request = Request::createFromGlobals();

$proxy = new Proxy($request, "http://www.yahoo.com");

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

$proxy->send();

```

