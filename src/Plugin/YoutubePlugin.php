<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class YoutubePlugin extends AbstractPlugin {

	protected $url_pattern = 'youtube.com';
	
	/*
	
	How do we extract direct links to YouTube videos?
	
	find html5player.js file that's embedded inside any video page source
	
	current js file is located at:
	
	//s.ytimg.com/yts/jsbin/html5player-en_US-vfl20EdcH/html5player.js
	
	//s.ytimg.com/yts/jsbin/html5player-en_US-vflaxmkJQ/html5player.js
	
	http://s.ytimg.com/yts/jsbin/html5player-en_US-vflP7pyW6/html5player.js
	
	//s.ytimg.com/yts/jsbin/html5player-new-en_US-vflnk2PHx/html5player-new.js
	
	look for
	
       c && a.set("signature", tt(c));
	
	*/
	
	function vn($a, $b){
		$c = $a[0];
		$a[0] = $a[$b % strlen($a)];
		$a[$b] = $c;
		return $a;
	}
	
	function sig_decipher($sig){

		// a.splice(0, b) = given array A, go to position 0 and start removing B number of items
		
		$sig = strrev($sig);
		$sig = $this->vn($sig, 32);
		
		$sig = substr($sig, 3);
		$sig = strrev($sig);
		
		$sig = substr($sig, 1);
		$sig = strrev($sig);

		return $sig;
    }
	
	private function get_youtube_links($html){

		if(preg_match('@url_encoded_fmt_stream_map["\']:\s*["\']([^"\'\s]*)@', $html, $matches)){
		
			$parts = explode(",", $matches[1]);
			
			//var_dump($parts); exit;
			
			foreach($parts as $p){
				$query = str_replace('\u0026', '&', $p);
				parse_str($query, $arr);
				
				$url = $arr['url'];
				
				if(isset($arr['sig'])){
					$url = $url.'&signature='.$arr['sig'];
				
				} else if(isset($arr['signature'])){
					$url = $url.'&signature='.$arr['signature'];
				
				} else if(isset($arr['s'])){
				
					// this is probably a VEVO/ads video... signature must be decrypted first!
					$signature = $this->sig_decipher($arr['s']);
					
					$url = $url.'&signature='.$signature;
				}
				
				$result[$arr['itag']] = $url;
			}
			
			return $result;
		}
		
		return false;
	}
	
	private function find_first_available($links, $itags){
	
		foreach($itags as $itag){
		
			if(isset($links[$itag])){
				return $links[$itag];
			}
		}
		
		return false;
	}
	
	public function onCompleted(ProxyEvent $event){
	
		$response = $event['response'];
		$output = $response->getContent();
		
		// do this on all youtube pages
		$output = preg_replace('@masthead-positioner">@', 'masthead-positioner" style="position:static;">', $output, 1);
		
		// replace future thumbnails with src=
		$output = preg_replace('#<img[^>]*data-thumb=#s','<img alt="Thumbnail" src=', $output);
		
		$links = $this->get_youtube_links($output);
		
		// we must be on a video page
		if($links){
		
			// the only ones supported by flowplayer
			$flv_itags = array(5, 34, 35);
			
			// supported by html5 player
			$mp4_itags = array(18, 22, 37, 38, 82, 84);
			
			// not supported by any player at the moment
			$webm_itags = array(43, 44, 46, 100, 102);
			
			// find first available mp4 video
			$mp4_url = $this->find_first_available($links, $mp4_itags);//$mp4_itags);
			
			$player = vid_player($mp4_url, 640, 390, 'mp4');
			
			// this div blocks our player controls
			$output = str_replace('<div id="theater-background" class="player-height"></div>', '', $output);
			
			// replace youtube player div block with our own
			$output = preg_replace('#<div id="player-api"([^>]*)>.*?<div id="watch-queue-mole"#s', 
			'<div id="player-api"$1>'.$player.'</div><div id="watch-queue-mole"', $output, 1);
		}
			
		$response->setContent($output);
	}
}

?>