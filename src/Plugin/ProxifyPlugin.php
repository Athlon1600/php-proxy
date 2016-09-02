<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;
use Proxy\Config;

class ProxifyPlugin extends AbstractPlugin {

	private $base_url = '';
	
	private function css_url($matches){
	
		$url = trim($matches[1]);
		
		if(stripos($url, 'data:') === 0){
			return $matches[0];
		}
		
		return str_replace($matches[1], proxify_url($matches[1], $this->base_url), $matches[0]);
	}
	
	/*
	
	this.params.logoImg&&(e="background-image: url("+this.params.logoImg+")")
	
	*/
	private function css_import($matches){
		return str_replace($matches[2], proxify_url($matches[2], $this->base_url), $matches[0]);
	}

	private function html_href($matches){
		
		$url = trim($matches[2]);
		
		// do not proxify magnet: links
		if(strpos($url, "magnet") === 0){
			return $matches[0];
		}
		
		// do we even need to proxify this URL?
		return str_replace($url, proxify_url($url, $this->base_url), $matches[0]);
	}

	private function html_src($matches){

		if(stripos(trim($matches[2]), 'data:') === 0){
			return $matches[0];
		}
		
		return str_replace($matches[2], proxify_url($matches[2], $this->base_url), $matches[0]);
	}

	private function form_action($matches){
	
		// $matches[1] holds single or double quote - whichever was used by webmaster
		
		// $matches[2] holds form submit URL - can be empty which in that case should be replaced with current URL
		if(!$matches[2]){
			$matches[2] = $this->base_url;
		}
		
		$new_action = proxify_url($matches[2], $this->base_url);
		
		// what is form method?
		$form_post = preg_match('@method=(["\'])post\1@i', $matches[0]) == 1;
		
		// take entire form string - find real url and replace it with proxified url
		$result = str_replace($matches[2], $new_action, $matches[0]);
		
		// must be converted to POST otherwise GET form would just start appending name=value pairs to your proxy url
		if(!$form_post){
		
			// may throw Duplicate Attribute warning but only first method matters
			$result = str_replace("<form", '<form method="POST"', $result);
			
			// got the idea from Glype - insert this input field to notify proxy later that this form must be converted to GET during http
			$result .= '<input type="hidden" name="convertGET" value="1">';
		}
		
		return $result;
	}
	
	public function onBeforeRequest(ProxyEvent $event){
		
		$request = $event['request'];
		
		// check if one of the POST pairs is convertGET - if so, convert this request to GET
		if($request->post->has('convertGET')){
			
			// we don't need this parameter anymore
			$request->post->remove('convertGET');
			
			// replace all GET parameters with POST data
			$request->get->replace($request->post->all());
			
			// remove POST data
			$request->post->clear();
			
			// This is now a GET request
			$request->setMethod('GET');
			
			$request->prepare();
		}
	}

	/*
	TODO:
			$input = preg_replace('#<meta[^>]*name=["\'](title|description|keywords)["\'][^>]*>#is', '', $input, 3);
            $input = preg_replace('#<link[^>]*rel=["\'](icon|shortcut icon)["\'][^>]*>#is', '', $input, 2);
			
					# Remove and record a <base> href
		$input = preg_replace_callback('#<base href\s*=\s*([\\\'"])?((?(1)(?(?<=")[^"]{1,2048}|[^\\\']{1,2048})|[^\s"\\\'>]{1,2048}))(?(1)\\1|)[^>]*>#i', 'html_stripBase', $input, 1);
		
				# Proxy url= values in meta redirects
		$input = preg_replace_callback('#content\s*=\s*(["\\\'])?[0-9]+\s*;\s*url=([\\\'"]|&\#39;)?((?(?<=")[^"]+|(?(?<=\\\')[^\\\']+|[^\\\'" >]+)))(?(2)\\2|)(?(1)\\1|)#i', 'html_metaRefresh', $input, 1);
		
		
		
		# Process forms
		$input = preg_replace_callback('#<form([^>]*)>(.*?)</form>#is', 'html_form', $input);
		
	*/
	
	public function onCompleted(ProxyEvent $event){
	
		// to be used when proxifying all the relative links
		$this->base_url = $event['request']->getUri();
		
		$response = $event['response'];
		$str = $response->getContent();
		
		$content_type = $response->headers->get('content-type');
		
		// DO NOT do any proxification on .js files
		if($content_type == 'text/javascript' || $content_type == 'application/javascript' || $content_type == 'application/x-javascript'){
			return;
		}
		
		// let's remove all frames?? does not protect against the frames created dynamically via javascript
		$str = preg_replace('@<iframe[^>]*>[^<]*<\\/iframe>@is', '', $str);
		
		// let's replace page titles with something custom
		if(Config::get('replace_title')){
			$str = preg_replace('/<title[^>]*>(.*?)<\/title>/ims', '<title>'.Config::get('replace_title').'</title>', $str);
		}
		
		/* css
		if {1} is not there then youtube breaks for some reason
		*/
		$str = preg_replace_callback('@[^a-z]{1}url\s*\((?:\'|"|)(.*?)(?:\'|"|)\)@im', array($this, 'css_url'), $str);
		
		// https://developer.mozilla.org/en-US/docs/Web/CSS/@import
		// TODO: what about @import directives that are outside <style>?
		$str = preg_replace_callback('/@import (\'|")(.*?)\1/i', array($this, 'css_import'), $str);
		
		// html .*? just in case href is empty...
		$str = preg_replace_callback('@href\s*=\s*(["\'])(.*?)\1@im', array($this, 'html_href'), $str);
		
		
		/*
		
		src= can be empty - then what?
		
		*/
		$str = preg_replace_callback('@src\s*=\s*(["|\'])(.*?)\1@i', array($this, 'html_src'), $str);
		
		// sometimes form action is empty - which means a postback to the current page
		$str = preg_replace_callback('@<form[^>]*action=(["\'])(.*?)\1[^>]*>@i', array($this, 'form_action'), $str);
		
		//$str = str_replace('document.forms[0]', 'document.forms[1]', $str);
		
		$response->setContent($str);
	}

}

?>