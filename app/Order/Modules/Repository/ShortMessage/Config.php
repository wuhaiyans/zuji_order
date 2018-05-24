<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * Config
 */
class Config {
	
	
	/**
	 * 短息模板ID
	 * @param type $appid
	 * @param type $scene
	 * @return boolean|string	成功是返回 短信模板ID；失败返回false
	 */
	public static function getCode( $appid, $scene ){

		$arr = [
			// 机市短息模板配置
			'1' => [
				'order_create' => 'SMS_113450944',
				'order_cancel' => '',
			],
			// 大疆
			'91' => [
				'order_create' => '',
				'order_cancel' => '',
			],
			'92' => [
				'order_create' => '',
				'order_cancel' => '',
			],
		];
		
		if( !isset($arr[$appid]) ){
			return false;
		}
		if( !isset($arr[$appid][$scene]) ){
			return false;
		}
		return $arr[$appid][$scene];
	}
	
}
