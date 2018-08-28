<?php

namespace App\Lib\Wechat;

use Illuminate\Support\Facades\Redis;

class WechatStorage{
	
	/**
	 * 
	 * @param array
	 */
	public function get( string $key ){
		
		// 读缓存
		$data = Redis::get($key);
		$data = json_decode($data,true);
		if( $data && isset($data['expires_time']) && time()<$data['expires_time'] ){
			return $data;
		}
		return null;
	}
	
	public function set( string $key, array $data, int $expires_time=0 ){
		if( $expires_time>0 ){
			$data['expires_time'] = $expires_time;
		}
		Redis::set($key, json_encode($data));
	}
	
}
