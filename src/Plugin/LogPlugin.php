<?php

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class LogPlugin extends AbstractPlugin {

	public function onHeadersReceived(ProxyEvent $event){
	
		// because this will be included in index.php - php-proxy-app/storage
		$storage_dir = realpath('./storage');
		
		if(!is_writable($storage_dir)){
			return;
		}
		
		$log_file = $storage_dir.'/'.date("Y-m-d").'.log';
		
		$request = $event['request'];
		$response = $event['response'];
		
		$data = array(
			'ip' => $_SERVER['REMOTE_ADDR'],
			'time' => time(),
			'url' => $request->getUri(),
			'status' => $response->getStatusCode(),
			'type' => $response->headers->get('content-type', 'unknown'),
			'size' => $response->headers->get('content-length', 'unknown')
		);
		
		$message = implode("\t", $data)."\r\n";
		
		@file_put_contents($log_file, $message, FILE_APPEND);
	}

}

?>