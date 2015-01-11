<?php

class XVideosPlugin extends AbstractPlugin {


	public function onBeforeResponse(FilterEvent $event){
	
	
		$response = $event->getResponse();
		
		if(preg_match('@flv_url=([^&]+)@', $response, $matches)){
			return rawurldecode($matches[1]);
		}
	
	
	}
	



}

?>