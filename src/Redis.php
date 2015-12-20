<?php

namespace Proxy;

class Redis {

	protected static $client;

	public function __construct(){
		// do nothing
	}
	
	public static function __callStatic($method_name, $arguments){

		$params = array(
			'scheme' => 'tcp',
			'host'   => '127.0.0.1',
			'port'   => 6379,
		);
		
		if(!static::$client){
			static::$client = new \Predis\Client($params);
		}
		
		return call_user_func_array(array(static::$client, $method_name), $arguments);
	}
}


?>