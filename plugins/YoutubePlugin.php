<?php

class YoutubePlugin extends AbstractPlugin {

	function vn($a, $b){
		$c = $a[0];
		$a[0] = $a[$b % strlen($a)];
		$a[$b] = $c;
		return $a;
	}

	function sig_decipher($sig){
		$a = strrev($sig);
		
		//$a = substr($a, 2);
		$a = $this->vn($a, 16);
		$a = $this->vn($a, 35);
		
		return $a;
	}
	
	private function get_youtube_links($html){

		if(preg_match('@url_encoded_fmt_stream_map["\']:\s*["\']([^"\'\s]*)@', $html, $matches)){
			$parts = explode(",", $matches[1]);
			
			foreach($parts as $p){
				$query = str_replace('\u0026', '&', $p);
				parse_str($query, $arr);
				
				$url = $arr['url'];
				
				if(isset($arr['s'])){
					$s = $this->sig_decipher($arr['s']);
					
					$url = $url.'&signature='.$s;
				}
				
				$result[$arr['itag']] = $url;
			}
			
			return $result;
		}
		
		return false;
	}
	
	public function onBeforeResponse(FilterEvent $event){
	
		$response = $event->getResponse();
		
		$output = $response->getContent();
		
		// do this on all youtube pages
		//$output = preg_replace('@masthead-positioner">@', 'masthead-positioner" style="position:static;">', $output, 1); 
		//$output = preg_replace('#<img[^>]*data-thumb=#s','<img alt="Thumbnail" src=', $output);	
		
		$links = $this->get_youtube_links($output);
		
		// these are flv links the only ones supported by flowplayer
		$itags = array(5, 34, 35);
		
		foreach($itags as $tag){
		
			if(isset($links[$tag])) {
				$vid_url = $links[$tag];
				
				$output = preg_replace('#<div id="player-api"([^>]*)>.*<div class="clear"#s', 
				'<div id="player-api"$1>'.vid_player($vid_url, 640, 390).'</div><div class="clear"', $output, 1);
		
				break;
			}
		}
		
		$response->setContent($output);
	}

}

?>