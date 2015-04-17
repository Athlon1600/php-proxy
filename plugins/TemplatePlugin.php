<?php

class TemplatePlugin extends AbstractPlugin {
	
	public function onCompleted(FilterEvent $event){

		$request = $event->getRequest();
		$response = $event->getResponse();
		
		$url = $request->getUri();
		
		// will not apply to streaming responses
		if(!is_html($response->headers->get('content-type'))){
			return;
		}
		
		$url_form = render_template("url_form", array(
			'url' => $url,
			'script_base' => SCRIPT_BASE
		));
		
		$output = $response->getContent();
		
		// does the html page contain <body> tag, if so insert our form right after <body> tag starts
		$output = preg_replace('@<body.*?>@is', '$0'.PHP_EOL.$url_form, $output, 1, $count);
		
		// <body> tag was not found, just put the form at the top of the page
		if($count == 0){
			$output = $url_form.$output;
		}
		
		$response->setContent($output);
	}

}

?>