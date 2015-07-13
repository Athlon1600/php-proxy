<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class DailyMotionPlugin extends AbstractPlugin {

	protected $url_pattern = 'dailymotion.com/video/';
	
	public function onCompleted(ProxyEvent $event){
		
		$response = $event['response'];
		
		$output = $response->getContent();
		
		if(preg_match('/video\/([^_]+)/', $event['request']->getUri(), $matches)){
		
			$id = $matches[1];
			
			// this better be available
			$str = file_get_contents("http://www.dailymotion.com/json/video/{$id}?fields=stream_h264_sd_url,stream_h264_hq_url,stream_h264_url,stream_h264_hd_url");
			$json = json_decode($str, true);
			
			if($json){
			
				$url = $json['stream_h264_sd_url'];
				
				$output = preg_replace('#\<div\sclass\=\"dmpi_video_playerv4(.*?)>.*?\<\/div\>#s', 
				'<div class="dmpi_video_playerv4${1}>'.vid_player($url, 620, 348).'</div>', $output, 1);
				
				$response->setContent($output);
			}
		}
	}

}


?>