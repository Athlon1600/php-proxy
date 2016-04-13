<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class DailyMotionPlugin extends AbstractPlugin {

	protected $url_pattern = 'dailymotion.com';
	
	public function onCompleted(ProxyEvent $event){
		
		$response = $event['response'];
		$output = $response->getContent();
		
		// http://www.dailymotion.com/json/video/{$id}?fields=stream_h264_sd_url,stream_h264_hq_url,stream_h264_url,stream_h264_hd_url
		if(preg_match('/"url":"([^"]+mp4[^"]*)"/m', $output, $matches)){
		
			$url = stripslashes($matches[1]);
			
			$player = element_find("player", $output);

			$output = substr_replace($output, 
			'<div class="dmp_Player-no-keyboard-focus" style="" id="player">'.vid_player($url, 1240, 478).'</div>', $player[0], $player[1] - $player[0]);
		}
		
		// too many useless scripts on this site
		$output = preg_replace('#<script[^>]*>.*?</script>#is', '', $output);
		
		$response->setContent($output);
	}

}


?>