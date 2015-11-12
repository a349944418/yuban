<?php
/**
 * redis 静态类
 */
Class RedisPool
{
	private static $connect;

	public static function getConnect()
	{
		if(!self::$connect){
			$redis = new Redis();
			$redis->connect('127.0.0.1', 6378);
			$redis->auth('a7234738');
			self::$connect = $redis;
		}
		return self::$connect;     
	}
}

?>