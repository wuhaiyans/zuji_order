<?php

namespace App\Order\Modules\Repository\ShortMessage;


/**
 * 买断支付成功短信通知
 * @author limin
 */
class BuyoutPayment{

	    /*
        * 买断支付成功短信通知
         * @$channel_id 渠道id 【必选】
         * @$class 场景名称 【必选】
        * @$data array $data 【必选】
        * [
        *      "mobile"=>"",手机号码
        *      "realName"=>"", 用户名称
        *      "buyoutPrice"=>"", 买断金
        * ]
        * @return json
        */
	public static function notify($channel_id,$class,$data){

		if(!$channel_id){
			return false;
		}
		if(!$class){
			return false;
		}
		$rule= [
				'mobile'=>'required',
				'realName'=>'required',
				'buyoutPrice'=>'required',
		];
		$validator = app('validator')->make($data, $rule);
		if ($validator->fails()) {
			return false;
		}
		// 短息模板
		$code = Config::getCode($channel_id, $class);
		if( !$code ){
			return false;
		}
		$array = [
				'realName'=>$data['realName'],
				'buyoutPrice'=>$data['buyoutPrice'],
		];
		if($channel_id == Config::CHANNELID_MICRO_RECOVERY){
			$smsContent['lianjie'] = $data['lianjie'];
		}
		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($data['mobile'], $code, $array);
	}
}
