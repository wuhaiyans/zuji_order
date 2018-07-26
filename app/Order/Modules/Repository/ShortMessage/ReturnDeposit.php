<?php

namespace App\Order\Modules\Repository\ShortMessage;

/**
 * OrderCancel
 *
 * @author limin
 */
class ReturnDeposit{
	/*
    * 押金退还短信通知
	 * @$channel_id 渠道id 【必选】
	 * @$class 场景名称 【必选】
    * @$data array $data 【必选】
    * [
    *      "mobile"=>"",手机号码
    *      "realName"=>"", 用户名称
    *      "orderNo"=>"", 订单编号
    *      "goodsName"=>"", 商品名称
    *      "tuihuanYajin"=>"", 退还押金
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
				'orderNo'=>'required',
				'goodsName'=>'required',
				'tuihuanYajin'=>'required',
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

		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($data['mobile'], $code, [
            'realName'=>$data['realName'],
            'orderNo'=>$data['orderNo'],
            'goodsName'=>$data['goodsName'],
            'tuihuanYajin'=>$data['tuihuanYajin']."元",
		]);
	}
	
}
