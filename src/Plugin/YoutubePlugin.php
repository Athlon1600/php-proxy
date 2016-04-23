<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

use Proxy\Html;

class YoutubePlugin extends AbstractPlugin {

	protected $url_pattern = 'youtube.com';
	
	// current URL
	private $youtube_url;
	
	// will return empty if youtube-dl not installed
	private function youtube_dl(){
	
		$result = array();
		
		$start = microtime(true);
		
		// --get-url
		// --dump-single-json
		$cmd = sprintf('youtube-dl -J %s', escapeshellarg($this->youtube_url));
		exec($cmd, $output, $ret);
		
		$end = microtime(true);
		
		if($ret == 0){
		
			$json = json_decode($output[0], true);
			
			// formats
			$formats = $json['formats'];
			
			foreach($formats as $vid){
				$result[$vid['format_id']] = $vid['url'];
			}
		}
		
		return $result;
	}
	
	private function get_youtube_links($html){

		if(preg_match('@url_encoded_fmt_stream_map["\']:\s*["\']([^"\'\s]*)@', $html, $matches)){
		
			$parts = explode(",", $matches[1]);
			
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
					return $this->youtube_dl();
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
	
		$this->youtube_url = $event['request']->getUrl();
		
		$response = $event['response'];
		$output = $response->getContent();
		
		// remove top banner that's full of ads
		$output = Html::remove("#header", $output);
		
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
			$output = Html::remove("#theater-background", $output);
			
			// replace youtube player div block with our own
			$output = Html::replace_inner("#player-api", $player, $output);
		}
		
		// causes too many problems...
		$output = Html::remove_scripts($output);
			
		$response->setContent($output);
	}
}

?>