<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class XVideosPlugin extends AbstractPlugin {

	protected $url_pattern = 'xvideos.com';
	
	public function onCompleted(ProxyEvent $event){
	
		$response = $event['response'];
		$html = $response->getContent();
		
		if(preg_match('@flv_url=([^&]+)@', $html, $matches)){
		
			$flv_url = rawurldecode($matches[1]);
			
			//
			$data = element_find("video-player-bg", $html);

			$html = substr_replace($html, '<div id="video-player-bg">'.vid_player($flv_url, 938, 476).'</div>', $data[0], $data[1] - $data[0]);
			
			$response->setContent($html);
		}
	}
}

?>