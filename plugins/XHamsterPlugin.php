<?php

class XHamsterPlugin extends AbstractPlugin {

	protected $url_pattern = 'xhamster.com';
	
	private function find_video($html){

		$file = false;
		
		if(preg_match('@mp4\'\s*file="(.*?)"@m', $html, $matches)){
			$file = rawurldecode($matches[1]);
		} else if(preg_match("@srv=&file=([^&]+)@s", $html, $matches)){
			$file = rawurldecode($matches[1]);
		}
		
		return $file;
	}

	public function onCompleted(FilterEvent $event){
	
		$response = $event->getResponse();
		$content = $response->getContent();
		
		$vid = $this->find_video($content);
	
		// we must be on a video page?
		if($vid){
	
			$content = preg_replace("@<div id='playerSwf'>.*?loader.*?<\/div>.*?<\/div>.*?<\/div>@s", 
			"<div id='playerSwf'>".vid_player($vid, 638, 505)."</div>", $content);
	
			$response->setContent($content);	
		}
	}
}

?>