<?php

class RedTubePlugin extends AbstractPlugin {

	public function onCompleted(FilterEvent $event){
	
		$output = $event->getResponse()->getContent();
	
		if(preg_match('@video_url=([^&]+)@', $output, $matches)){
			$vid_url = rawurldecode($matches[1]);
			
			//var_dump($vid_url);
			
			$player = '<video width="650" height="365" controls autoplay>
							<source src="'.$vid_url.'" type="video/mp4">
						Your browser does not support the video tag.
					</video>';
					
			$player = vid_player($vid_url, 650, 365);
					
			$output = preg_replace('@<div id="redtube_flv_player"(.*?)>.*?<noscript>.*?<\/noscript>.*?<\/div>@s', 
			'<div id="redtube_flv_player"$1>'.$player.'</div>', $output);
			
			
			$event->getResponse()->setContent($output);
		}
	}

}

?>