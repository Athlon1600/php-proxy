<?php

class ProxifyPlugin extends AbstractPlugin {

	function html_href($matches){
		return 'href=\''.proxify_url($matches[1]).'\'';
	}

	function html_src($matches){

		if(stripos(trim($matches[1]), 'data:') === 0){
			return $matches[0];
		}
		
		return 'src="'.proxify_url($matches[1]).'"';
	}

	function html_action($matches){

		$new_action = proxify_url($matches[1]);
		$result = str_replace($matches[1], $new_action, $matches[0]);
		
		// change form method to POST!!!
		$result = str_replace("<form", '<form method="POST"', $result);
		return $result;
	}

	// request response headers content_type
	public function onBeforeResponse(FilterEvent $event){
	
		$response = $event->getResponse();
	
	
		//$str = proxify_css($str);
		
		$str = $response->getContent();
		
		//var_dump("before: ".strlen($str));
		
		
		// let's remove all frames??
		
		
		$str = preg_replace('@<iframe[^>]+>.*?<\\/iframe>@is', '', $str);
		
		
		// html
		$str = preg_replace_callback('@href=["|\'](.+?)["|\']@im', array($this, 'html_href'), $str);
		$str = preg_replace_callback('@src=["|\'](.+?)["|\']@i', array($this, 'html_src'), $str);
		$str = preg_replace_callback('@<form[^>]*action=["|\'](.+?)["|\'][^>]*>@i', array($this, 'html_action'), $str);
		
		//var_dump("after: ".strlen($str));
		
		$response->setContent($str);
		
		//return $response;
	}



}

?>