<?php

class XVideosPlugin extends AbstractPlugin {

	protected $url_pattern = 'xvideos.com';
	
	public function onCompleted(FilterEvent $event){
	
		$response = $event->getResponse();
		
		if(preg_match('@flv_url=([^&]+)@', $response->getContent(), $matches)){
			
			$flv_url = rawurldecode($matches[1]);

			$output = preg_replace('@<div id="player.*?<\\/div>@s', '<div id="player">'.vid_player($flv_url, 588, 476).'</div>', $response->getContent(), 1);
			
			$response->setContent($output);
		}
	
	}

}

?>