<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * Config
 */
class SceneConfig {
	/**
	 * 场景:<b>【订单下单】</b>
	 */
	const ORDER_CREATE = 'OrderCreate';

    /**
     * 场景:<b>【支付成功】</b>
     */
    const ORDER_PAY = 'OrderPay';

	/**
	 * 场景:<b>【分期扣款】</b>
	 */
	const INSTALMENT_WITHHOLD = 'InstalmentWithhold';
	/**
     * 场景：<b>【申请退货】</b>
     */
    const RETURN_APPLY = 'ReturnApply';
    /**
     * 场景：<b>【退货审核通过】</b>
     */
    const RETURN_APPLY_AGREE = 'ReturnApplyAgree';
    /**
     * 场景：<b>【退货审核不通过】</b>
     */
    const RETURN_APPLY_DISAGREE = 'ReturnApplyDisagree';
    /**
     * 场景：<b>【退货检测合格】</b>
     */
    const RETURN_CHECK_OUT = 'ReturnCheckOut';
    /**
     * 场景：<b>【退货检测不合格】</b>
     */
    const RETURN_UNQUALIFIED = 'ReturnUnqualified';
    /**
     * 场景：<b>【收到客户退货手机】</b>
     */
    const RETURN_DELIVERY = 'ReturnDelivery';
}
