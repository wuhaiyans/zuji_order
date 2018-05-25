<?php
namespace App\Lib\Common;

use App\Lib\Curl;


/**
 * SmsApi
 *
 * @author Administrator
 */
class SmsApi {

	private static $service_id = '110001';
	private static $key = '2a04714403784e17a8c2e5fa3f7bb15f';
	//private $tongzhi_url = 'http://dev-psl-server.huanjixia.com/sms-interface';
	private static $tongzhi_url = 'http://push.huanjixia.com/sms-interface';
	//private $code_url = 'http://dev-psl-server.huanjixia.com/service/captcha';
	private static $code_url = 'http://push.huanjixia.com/service/captcha';

	/**
	 * 发送短息验证码
	 * @param string $mobile 手机号
	 * @return boolean
	 */
	public static function sendCode($mobile) {

		$time = time();  // 当前时间
		$data = [
			'_head' => [
				'_version' => '0.01', // 接口版本
				'_msgType' => 'request', // 类型
				'_interface' => 'send', // 接口名称
				'_remark' => '', // 备注
				'_invokeId' => microtime(true) . '.' . rand(1000, 9999), // 流水号
				'_callerServiceId' => self::$service_id, // 
				'_groupNo' => '1', // 服务组ID，固定值1
				'_timestamps' => $time, // 当前时间戳
			],
			'_param' => [
				'smsSign' => '12', // 
				'phones' => $mobile, // 手机号
				'templateCode' => 'SMS_113450943', // 验证码模板ID
				'templateParam' => [],
			],
		];
		$json = json_encode($data);  // 参数序列化
		$signature = md5($json . '_' . self::$key); // 参数签名
		// debug输出
		//$debug = ['url' => self::$code_url, 'request' => json_decode($json, true)];

		// curl
		$response = Curl::post(self::$code_url, $json, [// header 头
					'HSB-OPENAPI-SIGNATURE:' . $signature, // 签名字符串
					'HSB-OPENAPI-CALLERSERVICEID:' . self::$service_id  // 服务ID
		]);

		// curl请求失败
		if (empty($response)) {
			return false;
		}

		// json解析
		$response_arr = json_decode($response, true);
		// 填充 请求结果
		$debug['response'] = json_decode($response, true);

		if (empty($response_arr) || !$response_arr) {
			// 短息接口协议错误
			return false;
		}
		if ($response_arr['_data']['_ret'] != 0) {
			// 发短信失败
			return false;
		}
		return true;
	}

	/**
	 * 验证手机短信
	 * @param string $mobile	 手机号
	 * @param string $code		 验证码
	 * @return boolean
	 */
	public static function verifyCode(string $mobile,string $code) {

		$time = time();  // 当前时间
		$data = [
			'_head' => [
				'_version' => '0.01', // 接口版本
				'_msgType' => 'request', // 类型
				'_interface' => 'verification', // 接口名称
				'_remark' => '', // 备注
				'_invokeId' => microtime(true) . '.' . rand(1000, 9999), // 流水号
				'_callerServiceId' => self::$service_id, // 
				'_groupNo' => '1', // 服务组ID，固定值1
				'_timestamps' => $time, // 当前时间戳
			],
			'_param' => [
				'smsSign' => '12', // 
				'phones' => $mobile, // 手机号
				'templateCode' => 'SMS_113450943', // 短息模板ID
				'code' => $code,
			],
		];
		$json = json_encode($data);  // 参数序列化
		$signature = md5($json . '_' . self::$key); // 参数签名
		// curl
		$response = Curl::post(self::$code_url, $json, [// header 头
					'HSB-OPENAPI-SIGNATURE:' . $signature, // 签名字符串
					'HSB-OPENAPI-CALLERSERVICEID:' . self::$service_id	 // 服务ID
				]);

		// curl请求失败
		if (empty($response)) {
			// 短息接口请求失败
			return false;
		}

		// json解析
		$response_arr = json_decode($response, true);
		// debug输出
		//$debug = ['request' => json_decode($json, true), 'response' => json_decode($response, true)];
		if (empty($response_arr) || !$response_arr) {
			// 短息接口协议错误
			return false;
		}
		if (!isset($response_arr['_data']['_ret'])) {
			// 短息接口协议错误
			return false;
		}
		if ($response_arr['_data']['_ret'] != 0) {
			// 短息接口协议错误
			return false;
		}
		return true;
	}

	/**
	 * 发送模板短息
	 * @param string $mobile	手机号
	 * @param string $templateCode	短息模板
	 * @param array $templateParam	模板参数
	 * @return boolean
	 */
	public static function sendMessage(string $mobile, string $templateCode, array $templateParam=[] ) {

		$data = [
			'_head' => [
				'_version' => '0.01', // 接口版本
				'_msgType' => 'request', // 类型
				'_interface' => 'smsSubmit', // 接口名称
				'_remark' => '', // 备注
				'_invokeId' => microtime(true) . '.' . rand(1000, 9999), // 流水号
				'_callerServiceId' => self::$service_id, // 
				'_groupNo' => '1', // 服务组ID，固定值1
				'_timestamps' => time(), // 当前时间戳
			],
			'_param' => [
//				'smsSign' => '3', // 
				'phones' => $mobile, // 手机号
				'templateCode' => $templateCode, // 短息模板ID
				'templateParam' => $templateParam,
			],
		];
		$json = json_encode($data);  // 参数序列化
		$signature = md5($json . '_' . self::$key); // 参数签名
		// curl
		$response = Curl::post(self::$tongzhi_url, $json, [// header 头
					'HSB-OPENAPI-SIGNATURE:' . $signature, // 签名字符串
					'HSB-OPENAPI-CALLERSERVICEID:' . self::$service_id  // 服务ID
		]);

		// curl请求失败
		if (empty($response)) {
			//\zuji\debug\Debug::error(\zuji\debug\Location::L_SMS, '短息接口请求失败', \zuji\Curl::getError());
			//set_error(\zuji\Curl::getError());
			return false;
		}

		// json解析
		$response_arr = json_decode($response, true);
		// debug输出
		// $debug = ['request' => json_decode($json, true), 'response' => json_decode($response, true)];
		
		if (empty($response_arr) || !$response_arr) {
			//set_error('短息接口协议错误');
			return false;
		}
		if ($response_arr['_data']['_ret'] != 0) {
			//set_error('发短信失败');
			return false;
		}
		return true;
	}

}
