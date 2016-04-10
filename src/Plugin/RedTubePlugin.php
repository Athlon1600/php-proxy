<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class RedTubePlugin extends AbstractPlugin {

	private function data_src($matches){
		return '<img src="'.$matches[1].'">';
	}
	
	public function onCompleted(ProxyEvent $event){
	
		$output = $event['response']->getContent();
		
		// preload images
		$output = preg_replace_callback('/<img[^>]+data-src="([^"]+)"[^>]*>/', array($this, 'data_src'), $output);

		// remove ads
		$output = preg_replace('/<script data-cfasync.*?<\/script>/sm', '', $output);
		
		// extract all videos
		preg_match_all('/"([0-9]+)":"([^"]*mp4[^"]*)"/im', $output, $matches, PREG_SET_ORDER);
		
		// by default, HD videos go first - we don't want that
		$matches = array_reverse($matches);
		
		if($matches){
		
			$player = element_find("redtube_flv_player", $output);

			if($player){
				$url = rawurldecode(stripslashes($matches[0][2]));
				
				$output = substr_replace($output, 
				'<div class="redtube-flv-player">'.vid_player($url, 973, 547, 'mp4').'</div>', $player[0], $player[1] - $player[0]);
			}
		}
		
		$event['response']->setContent($output);
	}

}

?>