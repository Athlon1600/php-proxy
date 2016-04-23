<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

use Proxy\Html;

class DailyMotionPlugin extends AbstractPlugin {

	protected $url_pattern = 'dailymotion.com';
	
	public function onCompleted(ProxyEvent $event){
		
		$response = $event['response'];
		$content = $response->getContent();
		
		// http://www.dailymotion.com/json/video/{$id}?fields=stream_h264_sd_url,stream_h264_hq_url,stream_h264_url,stream_h264_hd_url
		if(preg_match('/"url":"([^"]+mp4[^"]*)"/m', $content, $matches)){
		
			$video = stripslashes($matches[1]);
			
			// generate our own player
			$player = vid_player($video, 1240, 478);
			
			$content = Html::replace_inner("#player", $player, $content);
		}
		
		// too many useless scripts on this site
		$content = Html::remove_scripts($content);
		
		$response->setContent($content);
	}

}


?>