<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class XHamsterPlugin extends AbstractPlugin {

	protected $url_pattern = 'xhamster.com';
	
	private function find_video($html){

		$file = false;
		
		if(preg_match("/file: '([^']+)'/", $html, $matches)){
			$file = rawurldecode($matches[1]);
		} else if(preg_match("@srv=&file=([^&]+)@s", $html, $matches)){
			$file = rawurldecode($matches[1]);
		}
		
		return $file;
	}
	
	private function img_sprite($matches){
		return str_replace($matches[1], proxify_url($matches[1], $matches[1]), $matches[0]);
	}

	public function onCompleted(ProxyEvent $event){
	
		$response = $event['response'];
		$content = $response->getContent();
		
		// remove ts_popunder stuff
		$content = preg_replace('/<script[^>]*no-popunder[^>]*><\/script>/m', '', $content);
		
		// remove analytics
		// <script[^>]*>[^<]*<\/script>
		$content = preg_replace('/<script[^<]*google-analytics\.com[^<]*<\/script>/im', '', $content);
		
		$content = preg_replace_callback('/<img[^>]*sprite=\'(.*?)\'/im', array($this, 'img_sprite'), $content);
		
		// are we on a video page?
		$vid = $this->find_video($content);
		
		//var_dump($vid);
		
		if($vid){
		
			$player_swf = element_find("playerSwf", $content);
			
			if($player_swf){
				$content = substr_replace($content, 
				"<div id='playerSwf'>".vid_player($vid, 638, 504)."</div>", $player_swf[0], $player_swf[1] - $player_swf[0]); 
			}
		}
		
		$response->setContent($content);
	}
}

?>