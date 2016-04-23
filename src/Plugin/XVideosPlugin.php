<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

use Proxy\Html;

class XVideosPlugin extends AbstractPlugin {

	protected $url_pattern = 'xvideos.com';
	
	public function onCompleted(ProxyEvent $event){
	
		$response = $event['response'];
		$html = $response->getContent();
		
		if(preg_match('@flv_url=([^&]+)@', $html, $matches)){
		
			$flv_url = rawurldecode($matches[1]);
			$player = vid_player($flv_url, 938, 476);
			
			// insert our own video player
			$html = Html::replace_inner("#video-player-bg", $player, $html);
			
			$response->setContent($html);
		}
	}
}

?>