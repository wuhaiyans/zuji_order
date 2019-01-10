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

		$url = config('ordersystem.OLD_ORDER_API');
		$data = [
			'version'		=> '1.0',
			'sign_type'		=> 'MD5',
			'sign'			=> '',
			'appid'			=> '1',
			'method'		=> 'zuji.sms.send',
			'timestamp' 	=> date("Y-m-d H:i:s"),
			'params'		=> [
				'type'			=> "SM_LOGIN",
				'mobile'		=> $mobile,
				'country_code'	=> "86",
			],
		];
		$info = Curl::post($url, json_encode($data));
		$info = json_decode($info,true);
		if ($info['code'] != 0) {
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
		$url = config('ordersystem.OLD_ORDER_API');
		$data = [
			'version'		=> '1.0',
			'sign_type'		=> 'MD5',
			'sign'			=> '',
			'appid'			=> '1',
			'method'		=> 'zuji.sms.verification',
			'timestamp' 	=> date("Y-m-d H:i:s"),
			'params'		=> [
				'mobile'	=> $mobile,
				'code'	=> $code,
			],
		];
		$info = Curl::post($url, json_encode($data));
		$info = json_decode($info,true);
		if ($info['code'] != 0) {
			// 发短信失败
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

		$url = config('ordersystem.OLD_ORDER_API');
		$data = [
			'version'		=> '1.0',
			'sign_type'		=> 'MD5',
			'sign'			=> '',
			'appid'			=> '1',
			'method'		=> 'zuji.sms.send_data',
			'timestamp' 	=> date("Y-m-d H:i:s"),
			'params'		=> [
				'mobile'	=> $mobile,
				'template'	=> $templateCode,
				'data'		=> $templateParam,
			],
		];
        LogApi::debug("[create]发送短信参数",$data);
		$info = Curl::post($url, json_encode($data));
		$info = json_decode($info,true);
        LogApi::debug("[create]发送短信回执参数",$info);
		\App\Order\Modules\Service\OrderSmsLog::create([
			'mobile' => $mobile,//手机号
			'template' => $templateCode,//短信模板
			'success' => $info['code'] != 0 ? 1 : 0,//短信发送结果（0：成功，1：失败）
			'params' => json_encode($templateParam),//短信发送的参数json串
			'result' => json_encode($info),//短信返回的结果json串
		]);
		if ($info['code'] != 0) {
			// 发短信失败
			return false;
		}
		return true;
	}

}
