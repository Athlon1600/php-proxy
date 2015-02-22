<?php

// all possible options will be stored
$config = array();

// plugins to load - plugins will be loaded in this exact order as in array
$config['plugins'] = array('HeaderRewrite', 'Proxify', 'Youtube', 'DailyMotion', 'RedTube', 'XHamster', 'XVideos');

// config params for log plugin
$config['log.enabled'] = false;
$config['log.file_types'] = array('text/html');


$config['youtube.html5_player'] = true;
//$config['youtube.video_quality'] = 1;

//$config['outgoing_ip'] = '123.123.123.13';
//$config['error_redirect'] = "https://unblockvideos.com/#error={error_msg}";


?>