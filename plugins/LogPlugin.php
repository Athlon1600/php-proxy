<?php

class LogPlugin extends AbstractPlugin {

	function onBeforeHeaders(FilterEvent $event){
	
		$request = $event->getRequest();
		$response = $event->getResponse();
		
		global $config;
		
		$enabled = $config->get("log.enabled");
		$file_types = $config->get("log.file_types");
		
		// logging disabled? don't bother
		if(!$enabled){
			return;
		}
		
		$vars = array(
			'ip' => $request->getClientIp(),
			'url' => $request->getUri(),
			'date' => date("F j, Y, g:i a")
		);
		
		$content_type = $response->headers->get("content_type");
		$content_type = clean_content_type($content_type);
		
		if(in_array($content_type, $file_types)){
		
			extract($vars);
			
			$line = "{$ip} - {$url} - {$date}\r\n";
			
			$file = "storage/log-".date('Y-m-d').".txt";
			
			file_put_contents($file, $line, FILE_APPEND | LOCK_EX);	
		}
		
		
		//throw new ProxyException("Log dir is not writable!");

		
	}

}

?>