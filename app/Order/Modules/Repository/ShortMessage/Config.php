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
     * 渠道配置<b>【努比亚渠道】</b>
     * @var int 16
     */
    const CHANNELID_NUBIYA = '16';
    /**
     * 渠道配置<b>【IOS渠道】</b>
     * @var int 22
     */
    const CHANNELID_IOS = '22';
    /**
     * 渠道配置<b>【安卓渠道】</b>
     * @var int 23
     */
    const CHANNELID_ANDROID = '23';

	
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
				SceneConfig::ORDER_CREATE 			 => 'SMS_113461042', //用户下单
                SceneConfig::ORDER_PAY 				 => 'SMS_113461043', //用户支付或授权 成功
                SceneConfig::ORDER_CANCEL           => 'SMS_113461044', //用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY         => 'SMS_113461046', //订单发货短信
                SceneConfig::ORDER_MONTH_RECEIVE    => 'SMS_113461146', //订单月租签收短信

				SceneConfig::INSTALMENT_WITHHOLD 	 => 'hsb_sms_b427f', //代扣扣款短信

                SceneConfig::RETURN_APPLY 			 => 'SMS_113461054', //申请退货
                SceneConfig::RETURN_APPLY_AGREE 	 => 'SMS_113461055', //退货审核通过
                SceneConfig::RETURN_APPLY_DISAGREE => 'SMS_113461056', //退货审核不通过
                SceneConfig::RETURN_CHECK_OUT 		 => 'SMS_113461058', //退货检测合格
                SceneConfig::RETURN_UNQUALIFIED 	 => 'SMS_113461059', //退货检测不合格
                SceneConfig::RETURN_DELIVERY 		 => 'SMS_113461057', //退货收到客户手机
                SceneConfig::REFUND_SUCCESS 		 => 'SMS_113461060', //退款成功

				SceneConfig::WITHHOLD_FAIL 		 => 'hsb_sms_99a6f', //扣款失败
				SceneConfig::WITHHOLD_WARMED	 	 => 'hsb_sms_16f75', //即将逾期
				SceneConfig::WITHHOLD_OVERDUE 		 => 'hsb_sms_7326b', //扣款失败生成逾期
				SceneConfig::REPAYMENT 				 => 'SMS_113461067', //提前还款短信


			],

			// 小程序
			self::CHANNELID_MINI_ZHIMA => [
				SceneConfig::ORDER_CREATE           =>'SMS_113461066',//用户下单
                SceneConfig::REFUND_SUCCESS 		 => 'SMS_113461060', //（取消订单、退货退款）退款成功
			],
			// 大疆
			self::CHANNELID_MINI_DAJIANG => [
				SceneConfig::ORDER_CREATE           =>'SMS_113460977',//用户下单
                SceneConfig::ORDER_PAY              =>'SMS_113460978',//用户支付或授权 成功
                SceneConfig::ORDER_CANCEL           =>'SMS_113460979',//用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY         =>'SMS_113460981', //订单发货短信
                SceneConfig::ORDER_DAY_RECEIVE      =>'SMS_113460982', //订单日租签收短信
                SceneConfig::REFUND_SUCCESS 		 => 'SMS_113460991', //（取消订单、退货退款）退款成功

			],
			// 极米
			self::CHANNELID_MINI_JIMI => [
				SceneConfig::ORDER_CREATE            =>'SMS_113461002',//用户下单
                SceneConfig::ORDER_PAY               =>'SMS_113461003',//用户支付或授权 成功
                SceneConfig::ORDER_CANCEL            =>'SMS_113461004',//用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY          =>'SMS_113461006', //订单发货短信
                SceneConfig::ORDER_DAY_RECEIVE       =>'SMS_113461007', //订单日租签收短信
                SceneConfig::REFUND_SUCCESS 		 => 'SMS_113461016', //（取消订单、退货退款）退款成功

			],
            // 努比亚
            self::CHANNELID_NUBIYA => [
                SceneConfig::ORDER_CREATE 			 =>'SMS_113461022', //用户下单
                SceneConfig::ORDER_PAY               =>'SMS_113461023',//用户支付或授权 成功
                SceneConfig::ORDER_CANCEL            =>'SMS_113461024',//用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY          =>'SMS_113461026', //订单发货短信
                SceneConfig::ORDER_MONTH_RECEIVE     =>'SMS_113461027', //订单月租签收短信
                SceneConfig::REFUND_SUCCESS 		 => 'SMS_113461036', //（取消订单、退货退款）退款成功


            ],
            // IOS
            self::CHANNELID_IOS => [

            ],
            // 安卓
            self::CHANNELID_ANDROID => [

            ],
		];
		if( isset($arr[$channelId][$scene]) ){
			return $arr[$channelId][$scene];
		}
		if( isset($arr[self::CHANNELID_OFFICAL][$scene])){
            return $arr[self::CHANNELID_OFFICAL][$scene];
        }

        return false;

	}
	
}
