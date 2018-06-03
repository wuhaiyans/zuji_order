<?php

namespace App\Lib\Wechat;


class WechatStorage{
	
	/**
	 * 
	 * @param array
	 */
	public function get( string $key ){
		// 读缓存
		if( isset($_SESSION[$key]) && time()<$_SESSION[$key]['expires_time'] ){
			return $_SESSION[$key];
		}
	}
	
	public function set( string $key, array $data, int $expires_time ){
		$data['expires_time'] = $expires_time;
		$_SESSION[$key] = $data;
	}
	
}
