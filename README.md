php-proxy
=========

Web-proxy script built on PHP, Symfony and Curl


Requirements
------------
PHP >= 5.3.3

cURL
```shell
apt-get install php5-curl
```

Installation
------------

If you're hosting this script on a server that provides shell access like a **vps** 
or a **dedicated server**, then using composer would be the fastest way to install it. Keep in mind that this is a **project** and not a library.

```shell
composer create-project athlon1600/php-proxy:dev-master /var/www/
```

However, if you're on a shared server with no shell access, then download a pre-installed version of php-proxy from the official website: [php-proxy.com](https://www.php-proxy.com)

Configuration
-------------

All the available configuration details are contained inside **config.php** file.

```php

// all possible options will be stored
$config = array();

// make it as long as possible for extra security...
$config['secret_key'] = '';

// plugins to load - plugins will be loaded in this exact order as in array
$config['plugins'] = array('AccessControl', 'HeaderRewrite', 'Proxify', 'Youtube', 'DailyMotion', 'RedTube', 'XHamster', 'XVideos');

// config params for log plugin
$config['log.enabled'] = false;
$config['log.file_types'] = array('text/html');


// if not empty - block URLs matching these patterns
$config['ac.url_blacklist'] = array(
	'github.com',
	'symfony.com'
);

// if not empty - block everything EXCEPT urls matching these
//$config['ac.url_whitelist'] = array();


// do not allow users from these countries
//$config['ac.country_blacklist'] = array('FR', 'DE', 'CN', 'CA');



$config['youtube.html5_player'] = true;
//$config['youtube.video_quality'] = 1;

//$config['outgoing_ip'] = '123.123.123.13';
//$config['error_redirect'] = "https://unblockvideos.com/#error={error_msg}";

```

Demo
-----

See this script in action:
<a href="https://unblockvideos.com/" target="_blank">UnblockVideos.com</a>

