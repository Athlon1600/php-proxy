<?php

class LogPlugin extends AbstractPlugin {

	function onBeforeHeaders(FilterEvent $event){
	
		$request = $event->getRequest();
		
		
		$str = $request->getClientIp()." - ".$request->getUri()."\r\n";
		
		
		file_put_contents("log.txt", $str, FILE_APPEND | LOCK_EX);
		
		
		//throw new ProxyException("Log dir is not writable!");

		
	}

}

?>