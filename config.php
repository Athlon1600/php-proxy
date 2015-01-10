<?php

// all possible options will be stored
$config = array();


$config['log.file_types'] = array('text/html');

/*
// for scripts spread out to multiple servers - on error let's redirect back to main site along with error message
//$config['error_redirect'] = "http://www.google.com/?error={error_type}&message={error_msg}";

// for extra privacy and speed - remove all javascript from pages
$config['remove_script'] = true;

// enable cookie functionality?
$config['enable_cookies'] = true;


	0 - urls are not unique - no encryption used apart from base64_encode(url) - very fast
	1 - all urls are unique to that session
	2 - urls are unique to the ip address that generated that url

$config['unique_urls'] = 0;


// replace the title of every page with this - or false to leave the title alone
$config['replace_title'] = 'Google Search';

// custom user agent - set it to false or null to use visitor's own user-agent
$config['user_agent'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

// which ip address to use when making a request?
//$config['ip_addr'] = false;//'';


// "blocked_ip" error will be thrown if any of the ips on this array tries using our proxy
$config['blocked_ips'] = array(
	'67.184.200.251',
	'123.123.123.123'
);

$config['redirect'] = function(){

	echo "do it now";

};

/*
// "blocked_domain" error if user tries accessing any of these domains
$config['blocked_domains'] = array(
	'youtube.com',
	'facebook.com',
	'xvideos.com',
	'redtube.com'
);
*/

// UNDER CONSTRUCTION!!!! means nothing at the moment
//$config['enable_logging'] = false;



?>