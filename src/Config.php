<?php

namespace Proxy;

// based off of this:
// http://v3.golaravel.com/api/source-class-Laravel.Config.html#3-235

class Config {

	private static $config = array();
	
	public static function get($key, $default = null){
		return self::has($key) ? static::$config[$key] : $default;
	}
	
	public static function set($key, $value){
		self::$config[$key] = $value;
	}
	
	public static function has($key){
		return isset(static::$config[$key]);
	}
	
	public static function load($path){
	
		if(file_exists($path)){
		
			// Successful includes, unless overridden by the included file, return 1.
			$data = require($path);
			
			if(is_array($data)){
				self::$config = array_merge(self::$config, $data);
			}
		}
	}

}

?>