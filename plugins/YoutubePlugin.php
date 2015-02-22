<?php

class YoutubePlugin extends AbstractPlugin {

	function vn($a, $b){
		$c = $a[0];
		$a[0] = $a[$b % strlen($a)];
		$a[$b] = $c;
		return $a;
	}
	
	function sig_decipher($sig){

		$a = $this->vn($sig, 61);
		
		$a = strrev($a);
		$a = substr($a, 1);

		$a = $this->vn($a, 31);
		$a = $this->vn($a, 36);

		$a = substr($a, 1);
		
        return $a;
    }
	
	private function get_youtube_links($html){

		if(preg_match('@url_encoded_fmt_stream_map["\']:\s*["\']([^"\'\s]*)@', $html, $matches)){
			$parts = explode(",", $matches[1]);
			
			//var_dump($parts); exit;
			
			foreach($parts as $p){
				$query = str_replace('\u0026', '&', $p);
				parse_str($query, $arr);
				
				$url = $arr['url'];
				
				$signature = isset($arr['sig']) ? $arr['sig'] : (isset($arr['signature']) ? $arr['signature'] : null);
				
				if($signature){
					$url = $url.'&signature='.$signature;
				} else if(isset($arr['s'])){
				
					$s = $this->sig_decipher($arr['s']);
				
					$url = $url.'&signature='.$s;
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
	
	public function onBeforeResponse(FilterEvent $event){
	
		$response = $event->getResponse();
		$output = $response->getContent();
		
		$url = $event->getRequest()->getUri();
		
		if(contains($url, "youtube.com")){
		
			// do this on all youtube pages
			$output = preg_replace('@masthead-positioner">@', 'masthead-positioner" style="position:static;">', $output, 1);
			$output = preg_replace('#<img[^>]*data-thumb=#s','<img alt="Thumbnail" src=', $output);
			
			
			if(contains($url, "youtube.com/watch")){
			
				$links = $this->get_youtube_links($output);
				
				// the only ones supported by flowplayer
				$flv_itags = array(5, 34, 35);
				$mp4_itags = array(18, 22, 37, 38, 82, 84);
				$webm_itags = array(43, 44, 46, 100, 102);
				
				//var_dump($links);
				
				
				global $config;
				
				$html5 = $config->get("youtube.html5_player");
				
				
				if($html5){
				
					// find mp4
					$mp4_url = $this->find_first_available($links, $mp4_itags);
					$mp4_url = proxify_url($mp4_url);
					
					$player = '<video width="100%" height="100%" controls autoplay>
									<source src="'.$mp4_url.'" type="video/mp4">
								Your browser does not support the video tag.
							</video>';
					
				} else {
				
					$vid_url = $this->find_first_available($links, $flv_itags);
					$player = vid_player($vid_url, 640, 390);
				}
				
				$output = str_replace('<div id="theater-background" class="player-height"></div>', '', $output);
				
				$output = preg_replace('#<div id="player-api"([^>]*)>.*<div class="clear"#s', 
				'<div id="player-api"$1>'.$player.'</div><div class="clear"', $output, 1);
			}
			
			$response->setContent($output);
		}
	}

}

?>