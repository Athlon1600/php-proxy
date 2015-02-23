<?php

// all possible options will be stored
$config = array();

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
$config['ac.url_whitelist'] = array();


$config['youtube.html5_player'] = true;
//$config['youtube.video_quality'] = 1;

//$config['outgoing_ip'] = '123.123.123.13';
//$config['error_redirect'] = "https://unblockvideos.com/#error={error_msg}";


?>