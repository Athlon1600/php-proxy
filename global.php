<?php

use Symfony\Component\HttpFoundation\Request;

function prepare_from_globals($url){

	$method = $_SERVER['REQUEST_METHOD'];
	$request = Request::create($url, $method, $method == 'POST' ? $_POST : array(), $_COOKIE, $_FILES, $_SERVER);

	return $request;	
}

// strip away extra parameters text/html; charset=UTF-8
function clean_content_type($content_type){
	return preg_replace('@;.*@', '', $content_type);
}

function is_html($content_type){

	$content_type = clean_content_type($content_type);
	
	$text = array(
		//'text/cmd',
		//'text/css',
		//'text/csv',
		//'text/example',
		'text/html'
		//'text/javascript',
		//'text/plain',
		//'text/rtf',
		//'text/vcard',
		//'text/vnd.abc',
		//'text/xml'
	);

	return in_array($content_type, $text);
}

function base64_url_encode($input){
	// = at the end is just padding to make the length of the str divisible by 4
	return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function base64_url_decode($input){
	return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) % 4, '=', STR_PAD_RIGHT));
}

function data_rot($data, $pass, $reverse = false){
	
	$data_len = strlen($data);
	$pass_len = strlen($pass);
	
	if($pass_len == 0) trigger_error("fnc:data_rot password must not be empty!", E_USER_ERROR);
	
	$result = str_repeat(' ', $data_len);

	for($i=0; $i<$data_len; $i++){
		$asc = ord($data[$i])+(ord($pass[$i%$pass_len]) * ($reverse ? -1 : 1));
		$result[$i] = chr($asc);
	}
	
	return $result;
}

function render_template($name, $vars = array()){
	
	// variables to be used within that template
	extract($vars);
	
	// this is where the views will be stored
	$file_path = 'templates/'.$name.'.php';
	
	ob_start();
	
	if(file_exists($file_path)){
		include($file_path);
	} else {
		die("Failed to load template: {$name}");
	}
	
	$contents = ob_get_contents();
	ob_end_clean();
	
	return $contents;
}

function encrypt_url($url){
	
	/*
	global $config;
	
	if($config['unique_urls'] === 2){
		$url = data_rot($url, USER_IP_LONG);
	}
	*/
	
	return base64_url_encode($url);
}

function decrypt_url($url){
	
	$url = base64_url_decode($url);
	
	return $url;
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

function proxify_url($url){
	$url = htmlspecialchars_decode($url);
	$url = rel2abs($url, URL); // URL is the base
	return SCRIPT_BASE.'?q='.encrypt_url($url);
}

function vid_player($url, $width, $height){

	$video_url = proxify_url($url); // proxify!
	$video_url = rawurlencode($video_url); // encode before embedding it into player's parameters
	
	$html = '<object id="flowplayer" width="'.$width.'" height="'.$height.'" data="'.PLAYER_URL.'" type="application/x-shockwave-flash">
 	 
       	<param name="allowfullscreen" value="true" />
		<param name="wmode" value="transparent" />
        <param name="flashvars" value=\'config={"clip":"'.$video_url.'", "plugins": {"controls": {"autoHide" : false} }}\' />
		
    </object>';
	
	return $html;
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