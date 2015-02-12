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

You can either write and inject your own plugins or edit config.php file that deals with all already pre-installed plugins.

Demo
-----

See this script in action:
<a href="https://unblockvideos.com/" target="_blank">UnblockVideos.com</a>

