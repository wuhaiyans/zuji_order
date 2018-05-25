<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * Config
 */
class Config {
	
	/**
	 * 渠道配置<b>【官方渠道】</b>
	 * @var int 1
	 */
	const CHANNELID_OFFICAL = 1;
	/**
	 * 渠道配置<b>【小程序渠道】</b>
	 * @var int 10
	 */
	const CHANNELID_MINI_ZHIMA = '10';
	/**
	 * 渠道配置<b>【大疆渠道】</b>
	 * @var int 14
	 */
	const CHANNELID_MINI_DAJIANG = '14';
	/**
	 * 渠道配置<b>【极米渠道】</b>
	 * @var int 15
	 */
	const CHANNELID_MINI_JIMI = '15';
	
	/**
	 * 短息模板ID
	 * @param type $channelId
	 * @param type $scene
	 * @return boolean|string	成功是返回 短信模板ID；失败返回false
	 */
	public static function getCode( $channelId, $scene ){
		$arr = [
			// 机市短息模板配置
			self::CHANNELID_OFFICAL => [
				SceneConfig::ORDER_CREATE => 'SMS_113450944',
			],
			// 小程序
			self::CHANNELID_MINI_ZHIMA => [
				SceneConfig::ORDER_CREATE => 'SMS_113450944',
			],
			// 大疆
			self::CHANNELID_MINI_DAJIANG => [
				SceneConfig::ORDER_CREATE => 'SMS_113450944',
			],
			// 极米
			self::CHANNELID_MINI_JIMI => [
				SceneConfig::ORDER_CREATE => 'SMS_113450944',
			],
		];
		
		if( !isset($arr[$channelId][$scene]) ){
			return false;
		}
		return $arr[$channelId][$scene];
	}
	
}
