<?php

use Proxy\Config;

// strip away extra parameters text/html; charset=UTF-8
function clean_content_type($content_type){
	return trim(preg_replace('@;.*@', '', $content_type));
}


if(!function_exists('starts_with')){
	
	function starts_with($haystack, $needles){
		foreach( (array)$needles as $n){
			if($n !== '' && stripos($haystack, $n) === 0){
				return true;
			}
		}
		
		return false;
	}
}

if(!function_exists('str_before')){
	
	function str_before($subject, $search){
		return $search === '' ? $subject : explode($search, $subject)[0];
	}
}

function is_html($content_type){
	return clean_content_type($content_type) == 'text/html';
}

function in_arrayi($needle, $haystack){
	return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

function re_match($pattern, $string){
	
	$quoted = preg_quote($pattern, '#');
	$translated = strtr($quoted, array(
		'\*' => '.*',
		'\?' => '.'
	));
	
	return preg_match("#^".$translated."$#i", $string) === 1;
}

// regular array_merge does not work if arrays have numeric keys...
function array_merge_custom(){
	
	$arr = array();
	$args = func_get_args();

	foreach( (array)$args as $arg){
		foreach( (array)$arg as $key => $value){
			$arr[$key] = $value;
		}
	}
	
	return $arr;
}

// rotate each string character based on corresponding ascii values from some key
function str_rot_pass($str, $key, $decrypt = false){
	
	// if key happens to be shorter than the data
	$key_len = strlen($key);
	
	$result = str_repeat(' ', strlen($str));
	
	for($i=0; $i<strlen($str); $i++){

		if($decrypt){
			$ascii = ord($str[$i]) - ord($key[$i % $key_len]);
		} else {
			$ascii = ord($str[$i]) + ord($key[$i % $key_len]);
		}
	
		$result[$i] = chr($ascii);
	}
	
	return $result;
}

function app_url(){
	if(Config::get('app_url')){
		return Config::get('app_url');
	}
	else{
		return (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	}
}

function render_string($str, $vars = array()){
	preg_match_all('@{([a-z0-9_]+)}@s', $str, $matches, PREG_SET_ORDER);
	
	foreach($matches as $match){
		extract($vars, EXTR_PREFIX_ALL, "_var");
		$var_val = ${"_var_".$match[1]};
		$str = str_replace($match[0], $var_val, $str);
	}
	
	return $str;
}

function render_template($file_path, $vars = array()){

	// variables to be used within that template
	extract($vars);
	
	ob_start();
	
	if(file_exists($file_path)){
		include($file_path);
	} else {
		die("Failed to load template: {$file_path}");
	}
	
	$contents = ob_get_contents();
	ob_end_clean();
	
	return $contents;
}

function add_http($url){

	if(!preg_match('#^https?://#i', $url)){
		$url = 'http://' . $url;
	}
	
	return $url;
}

function time_ms(){
	return round(microtime(true) * 1000);
}

function base64_url_encode($input){
	// = at the end is just padding to make the length of the str divisible by 4
	return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function base64_url_decode($input){
	return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) % 4, '=', STR_PAD_RIGHT));
}

function url_encrypt($url, $key = false){

	if($key){
		$url = str_rot_pass($url, $key);
	} else if(Config::get('encryption_key')){
		$url = str_rot_pass($url, Config::get('encryption_key'));
	}
	
	return Config::get('url_mode') ? base64_url_encode($url) : rawurlencode($url);
}

function url_decrypt($url, $key = false){

	$url = Config::get('url_mode') ? base64_url_decode($url) : rawurldecode($url);
	
	if($key){
		$url = str_rot_pass($url, $key, true);
	} else if(Config::get('encryption_key')){
		$url = str_rot_pass($url, Config::get('encryption_key'), true);
	}
	
	return $url;
}

// www.youtube.com TO proxy-app.com/index.php?q=encrypt_url(www.youtube.com)
function proxify_url($url, $base_url = ''){
	
	$url = htmlspecialchars_decode($url);
	
	if($base_url){
		$base_url = add_http($base_url);
		$url = rel2abs($url, $base_url);
	}
	
	return app_url().'?q='.url_encrypt($url);
}

function rel2abs($rel, $base)
{
	if (strpos($rel, "//") === 0) {
		return "http:" . $rel;
	}
	
	if($rel == ""){
		return "";
	}
	
	/* return if  already absolute URL */
	if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
	/* queries and  anchors */
	if ($rel[0] == '#' || $rel[0] == '?') return $base . $rel;
	/* parse base URL  and convert to local variables:
	$scheme, $host,  $path */
	extract(parse_url($base));
	/* remove  non-directory element from path */
	@$path = preg_replace('#/[^/]*$#', '', $path);
	/* destroy path if  relative url points to root */
	if ($rel[0] == '/') $path = '';
	/* dirty absolute  URL */
	$abs = "$host$path/$rel";
	/* replace '//' or  '/./' or '/foo/../' with '/' */
	$re = array(
		'#(/\.?/)#',
		'#/(?!\.\.)[^/]+/\.\./#'
	);
	for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
	}

	/* absolute URL is  ready! */
	return $scheme . '://' . $abs;
}

?>
