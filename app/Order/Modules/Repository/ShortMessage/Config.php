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
	 * @var int 15
	 */
	const CHANNELID_MINI_DAJIANG = '15';
	/**
	 * 渠道配置<b>【极米渠道】</b>
	 * @var int 14
	 */
	const CHANNELID_MINI_JIMI = '14';
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
     * 渠道配置<b>【微回收渠道】</b>
     * @var int 33
     */
    const CHANNELID_MICRO_RECOVERY  = '33';
    /**
     * 渠道配置<b>【花呗先享渠道】</b>
     * @var int 35
     */
    const CHANNELID_FLOWER_ENJOY  = '35';
    /**
     * 渠道配置<b>【校园门店】</b>
     * @var int 37
     */
    const CHANNELID_SCHOOL_STORE  = '37';

	
	/**
	 * 短息模板ID
	 * @param type $channelId
	 * @param type $scene
	 * @return boolean|string	成功是返回 短信模板ID；失败返回false
	 */
	public static function getCode( $channelId, $scene ){
		$arr = [
			// 拿趣用短息模板配置
			self::CHANNELID_OFFICAL => [
				SceneConfig::ORDER_CREATE 			 	=> 'SMS_113461042', //用户下单
                SceneConfig::ORDER_PAY 				 	=> 'SMS_113461043', //用户支付或授权 成功
                SceneConfig::ORDER_CANCEL               	=> 'SMS_113461044', //用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY            	=> 'SMS_113461046', //订单发货短信
                SceneConfig::ORDER_MONTH_RECEIVE    	    => 'SMS_113461146', //订单月租签收短信
                SceneConfig::ORDER_MONTH_BEFORE_MONTH_ENDING =>'SMS_113461128',//订单到期前一个月发送信息-月租
                SceneConfig::ORDER_MONTH_BEFORE_WEEK_ENDING  =>'SMS_113461129',//订单到期前一周发送信息-月租
                SceneConfig::ORDER_MONTH_OVER_MONTH_ENDING   =>'SMS_113461130',//订单逾期一个月发送信息-月租
                SceneConfig::ORDER_DAY_BEFORE_ONE_ENDING     =>'SMS_113461158',//订单到期前一天发送信息-短租


                SceneConfig::RETURN_APPLY 			 	=> 'SMS_113461054', //申请退货
                SceneConfig::RETURN_APPLY_AGREE 	 	    => 'SMS_113461055', //退货审核通过
                SceneConfig::RETURN_APPLY_DISAGREE 		=> 'SMS_113461056', //退货审核不通过
                SceneConfig::RETURN_CHECK_OUT 		 	=> 'SMS_113461058', //退货检测合格
                SceneConfig::RETURN_UNQUALIFIED 	 	=> 'SMS_113461059', //退货检测不合格
                SceneConfig::RETURN_DELIVERY 		 	=> 'SMS_113461057', //退货收到客户手机
                SceneConfig::REFUND_SUCCESS 			=> 'SMS_113461060', //退款成功

				SceneConfig::INSTALMENT_WITHHOLD 	 	=> 'SMS_113461050', //代扣扣款短信
				SceneConfig::WITHHOLD_FAIL 		 		=> 'SMS_113461051', //扣款失败
				SceneConfig::WITHHOLD_WARMED	 	 	    => 'SMS_113461052', //即将逾期
				SceneConfig::WITHHOLD_OVERDUE 		 	=> 'SMS_113461053', //扣款失败生成逾期
				SceneConfig::REPAYMENT 				 	=> 'SMS_113461067', //提前还款短信
				SceneConfig::WITHHOLD_FAIL_INITIATIVE   => 'SMS_113461062', //扣款失败主动发送短信

				SceneConfig::WITHHOLD_ADVANCE_ONE      	=> 'SMS_113461197', //提前一天还款短信
				SceneConfig::WITHHOLD_ADVANCE_THREE   	=> 'SMS_113461196', //提前三天 还款短信
				SceneConfig::WITHHOLD_ADVANCE_SEVEN   	=> 'SMS_113461177', //提前七天 还款短信

				SceneConfig::WITHHOLD_OVERDUEONE   		=> 'SMS_113461204', //扣款失败生成逾期 一天
				SceneConfig::WITHHOLD_OVERDUETHREE   	=> 'SMS_113461205', //扣款失败生成逾期 三天


				//还机
				SceneConfig::GIVEBACK_CREATE 			    => 'SMS_113461131', //还机申请
				SceneConfig::GIVEBACK_CONFIRMDELIVERY 	=> 'SMS_113461132', //还机确认收货 有剩余的租金
				SceneConfig::GIVEBACK_CONFIRMNOWITH     	=> 'SMS_113461159', //还机确认收货 无剩余的租金
				SceneConfig::GIVEBACK_WITHHOLDSUCCESS 	=> 'SMS_113461135', //系统执行代扣成功后发送
				SceneConfig::GIVEBACK_WITHHOLDFAIL	 	=> 'SMS_113461136', //系统执行代扣成功后发送
				SceneConfig::GIVEBACK_PAYMENT	 		    => 'SMS_113461137', //财务收到用户剩余租金成功时发送
				SceneConfig::GIVEBACK_EVANOWITYESENONO	=> 'SMS_113461139', //库管点击检测不合格、输入赔偿金额时发送
				SceneConfig::GIVEBACK_EVANOWITYESENO   	=> 'SMS_113461140', //库管点击检测不合格、输入赔偿金额时发送
				SceneConfig::GIVEBACK_EVANOWITNOENONO	=> 'SMS_113461141', //库管点击检测不合格、输入赔偿金额时发送
				SceneConfig::GIVEBACK_EVANOWITNOENO	 	=> 'SMS_113461142', //库管点击检测不合格、输入赔偿金额时发送
				SceneConfig::GIVEBACK_RETURNDEPOSIT	 	=> 'SMS_113461138', //财务系统完成押金退还时发送
				//买断
				SceneConfig::BUYOUT_CONFIRM					=> 'SMS_113461144', //买断确认短信
				SceneConfig::BUYOUT_PAYMENT					=> 'SMS_113461145', //买断支付短信
				SceneConfig::BUYOUT_PAYMENT_END				=> 'SMS_113461161', //买断完成短信

                //退押金
                SceneConfig::RETURN_DEPOSIT				=> 'SMS_113461138', //财务系统完成押金退还时发送

				SceneConfig::CRONREPAYMENT				=> 'SMS_113461070', //月初发送提前还款短信
                SceneConfig::DESTINE_CREATE              => 'SMS_113461183', //订金退款申请短信
                SceneConfig::DESTINE_REFUND              => 'SMS_113461184  ',//订金退款成功短信

				// 续租
				SceneConfig::RELETSUCCESS               => 'SMS_113461214  ',//续租成功

			],

			// 小程序
			self::CHANNELID_MINI_ZHIMA => [
				SceneConfig::ORDER_CREATE           	=> 'SMS_113461066',//用户下单
                SceneConfig::REFUND_SUCCESS 		 	=> 'SMS_113461060', //（取消订单、退货退款）退款成功
			],

			// 大疆
			self::CHANNELID_MINI_DAJIANG => [
				SceneConfig::ORDER_CREATE           	=> 'SMS_113460977',//用户下单
                SceneConfig::ORDER_PAY              	=> 'SMS_113460978',//用户支付或授权 成功
                SceneConfig::ORDER_CANCEL           	=> 'SMS_113460979',//用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY         	=> 'SMS_113460981', //订单发货短信
                SceneConfig::ORDER_DAY_RECEIVE      	=> 'SMS_113460982', //订单日租签收短信
                SceneConfig::REFUND_SUCCESS 		 	=> 'SMS_113460991', //（取消订单、退货退款）退款成功

				SceneConfig::INSTALMENT_WITHHOLD 	 	=> 'SMS_113460985', //代扣扣款短信
				SceneConfig::WITHHOLD_FAIL 		 		=> 'SMS_113460986', //扣款失败
				SceneConfig::WITHHOLD_WARMED	 	 	    => 'SMS_113460987', //即将逾期
				SceneConfig::WITHHOLD_OVERDUE 		 	=> 'SMS_113460988', //扣款失败生成逾期
				SceneConfig::REPAYMENT 				 	=> 'SMS_113460994', //提前还款短信

                SceneConfig::RETURN_APPLY_DISAGREE    => 'SMS_113460980', //退货审核不通过
                SceneConfig::RETURN_CHECK_OUT 		 => 'SMS_113460989', //退货检测合格
                SceneConfig::RETURN_UNQUALIFIED 	     => 'SMS_113460990', //退货检测不合格


			],
			// 极米
			self::CHANNELID_MINI_JIMI => [
				SceneConfig::ORDER_CREATE            	=> 'SMS_113461002',//用户下单
                SceneConfig::ORDER_PAY               	=> 'SMS_113461003',//用户支付或授权 成功
                SceneConfig::ORDER_CANCEL            	=> 'SMS_113461004',//用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY          	=> 'SMS_113461006', //订单发货短信
                SceneConfig::ORDER_DAY_RECEIVE       	=> 'SMS_113461007', //订单日租签收短信
                SceneConfig::REFUND_SUCCESS 		 	=> 'SMS_113461016', //（取消订单、退货退款）退款成功

				SceneConfig::INSTALMENT_WITHHOLD 	 	=> 'SMS_113461010', //代扣扣款短信
				SceneConfig::WITHHOLD_FAIL 		 		=> 'SMS_113461011', //扣款失败
				SceneConfig::WITHHOLD_WARMED	 	 	    => 'SMS_113461012', //即将逾期
				SceneConfig::WITHHOLD_OVERDUE 		 	=> 'SMS_113461013', //扣款失败生成逾期
				SceneConfig::REPAYMENT 				 	=> 'SMS_113461019', //提前还款短信

                SceneConfig::RETURN_APPLY_DISAGREE    => 'SMS_113461005', //退货审核不通过
                SceneConfig::RETURN_CHECK_OUT 		 => 'SMS_113461014', //退货检测合格
                SceneConfig::RETURN_UNQUALIFIED 	     => 'SMS_113461015', //退货检测不合格


			],
            // 努比亚
            self::CHANNELID_NUBIYA => [
                SceneConfig::ORDER_CREATE 			=> 'SMS_113461022', //用户下单
                SceneConfig::ORDER_PAY               	=> 'SMS_113461023',//用户支付或授权 成功
                SceneConfig::ORDER_CANCEL            	=> 'SMS_113461024',//用户/后台/自动任务取消订单
                SceneConfig::ORDER_DELIVERY          	=> 'SMS_113461026', //订单发货短信
                SceneConfig::ORDER_MONTH_RECEIVE     => 'SMS_113461027', //订单月租签收短信
                SceneConfig::REFUND_SUCCESS 		 	=> 'SMS_113461036', //（取消订单、退货退款）退款成功

				SceneConfig::INSTALMENT_WITHHOLD 	 	=> 'SMS_113461030', //代扣扣款短信
				SceneConfig::WITHHOLD_FAIL 		 		=> 'SMS_113461031', //扣款失败
				SceneConfig::WITHHOLD_WARMED	 	 	    => 'SMS_113461032', //即将逾期
				SceneConfig::WITHHOLD_OVERDUE 		 	=> 'SMS_113461033', //扣款失败生成逾期
				SceneConfig::REPAYMENT 				 	=> 'SMS_113461039', //提前还款短信

                SceneConfig::RETURN_APPLY_DISAGREE    => 'SMS_113461025', //退货审核不通过
                SceneConfig::RETURN_CHECK_OUT 		 => 'SMS_113461034', //退货检测合格
                SceneConfig::RETURN_UNQUALIFIED 	     => 'SMS_113461035', //退货检测不合格

            ],
            // IOS
            self::CHANNELID_IOS => [
                SceneConfig::ORDER_CREATE 			 	=> 'SMS_000000000', //用户下单
            ],
            // 安卓
            self::CHANNELID_ANDROID => [
                SceneConfig::ORDER_CREATE 			 	=> 'SMS_000000000', //用户下单
            ],
            // 微回收
            self::CHANNELID_MICRO_RECOVERY => [
                SceneConfig::ORDER_CREATE 			 	=> 'SMS_113461173', //用户下单
                SceneConfig::RETURN_CHECK_OUT 		 	=> 'SMS_113461175', //退货检测合格
                SceneConfig::REFUND_SUCCESS 			    => 'SMS_113461211', //退款成功

                SceneConfig::ORDER_PAY 				 	=> 'SMS_113461206', //用户支付或授权 成功
                SceneConfig::ORDER_DELIVERY            	=> 'SMS_113461207', //订单发货短信

				SceneConfig::ORDER_MONTH_BEFORE_MONTH_ENDING	=> 'SMS_113461208', // 微回收订单将在1个月租期到期
				SceneConfig::ORDER_MONTH_BEFORE_WEEK_ENDING	=> 'SMS_113461209', // 微回收订单将在1周之后到期
				SceneConfig::RETURN_DEPOSIT 			            => 'SMS_113461211', // 微回收退还押金成功
				SceneConfig::GIVEBACK_RETURNDEPOSIT           	=> 'SMS_113461211', // 微回收退还押金成功

				SceneConfig::BUYOUT_CONFIRM			=> 'SMS_113461144', //买断确认短信
				SceneConfig::BUYOUT_PAYMENT			=> 'SMS_113461210', //微回收买断支付短信
				SceneConfig::BUYOUT_PAYMENT_END		=> 'SMS_113461210', //微回收买断完成短信

            ],
            // 花呗先享
            self::CHANNELID_FLOWER_ENJOY => [
                SceneConfig::ORDER_CREATE 			 	=> 'SMS_000000000', //用户下单
                SceneConfig::RETURN_TOKIO 			 	=> 'SMS_113461198', //花呗分期退款
            ],
            // 校园门店
            self::CHANNELID_SCHOOL_STORE => [
                SceneConfig::ORDER_CREATE 			 	=> 'SMS_113461219', //用户下单
                SceneConfig::ORDER_DELIVERY            	=> 'SMS_113461220', //订单发货短信
                SceneConfig::GIVEBACK_CREATE 			=> 'SMS_000000000', //还机申请
                SceneConfig::GIVEBACK_CONFIRMDELIVERY 	=> 'SMS_000000000', //还机确认收货 有剩余的租金
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
