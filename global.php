<?php

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

function replace_placeholders($str, $callback = null){

	global $tpl_vars;

	preg_match_all('@{(.+?)}@s', $str, $matches, PREG_SET_ORDER);
	
	foreach($matches as $match){
	
		$var_val = $tpl_vars[$match[1]];
		
		if(function_exists($callback)){
			$var_val = @call_user_func($callback, $var_val);
		}
		
		$str = str_replace($match[0], $var_val, $str);
	}
	
	return $str;
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


use Symfony\Component\HttpFoundation\Request;

function request_set_url(Request $request, $url){

	$components = parse_url($url);
	
	$server = $_SERVER;
	
	// unset all in the beginning
	unset($server['HTTPS']);
	unset($server['HTTP_HOST']);
	unset($server['SERVER_NAME']);
	unset($server['REQUEST_URI']);
	unset($server['QUERY_STRING']);
	
	if(isset($components['scheme']) && $components['scheme'] == 'https'){
		$server['HTTPS'] = 'on';
	}
	
	$server['HTTP_HOST'] = $components['host'];
	$server['SERVER_NAME'] = $components['host'];
	
	if(isset($components['path'])){
		$server['REQUEST_URI'] = $components['path'];
	}
	
	if(isset($components['query'])){
		$server['QUERY_STRING'] = $components['query'];
	}
	
	return $request->duplicate(null, null, null, null, null, $server);
}

function rel2abs($rel, $base)
{
	if (strpos($rel, "//") === 0) {
		return "http:" . $rel;
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