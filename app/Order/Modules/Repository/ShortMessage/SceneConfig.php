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
     * 场景:<b>【订单未支付取消】</b>
     */
    const ORDER_CANCEL = 'OrderCancel';

    /**
     * 场景:<b>【支付成功】</b>
     */
    const ORDER_PAY = 'OrderPay';
    /**
     * 场景:<b>【订单发货】</b>
     */
    const ORDER_DELIVERY = 'OrderDelivery';
    /**
     * 场景:<b>【订单签收-日租】</b>
     */
    const ORDER_DAY_RECEIVE = 'OrderDayReceive';
    /**
     * 场景:<b>【订单签收-月租】</b>
     */
    const ORDER_MONTH_RECEIVE = 'OrderMonthReceive';
    /**
     * 场景:<b>【订单到期前一个月发送信息-月租】</b>
     */
    const ORDER_MONTH_BEFORE_MONTH_ENDING = 'OrderMonthBeforeMonthEnding';
    /**
     * 场景:<b>【订单到期前一周发送信息-月租】</b>
     */
    const ORDER_MONTH_BEFORE_WEEK_ENDING = 'OrderMonthBeforeWeekEnding';
    /**
     * 场景:<b>【订单逾期一个月发送信息-月租】</b>
     */
    const ORDER_MONTH_OVER_MONTH_ENDING = 'OrderMonthOverMonthEnding';

	/**
	 * 场景:<b>【分期扣款】</b>
	 */
	const INSTALMENT_WITHHOLD = 'InstalmentWithhold';
    /**
     * 场景:<b>【扣款失败】</b>
     */
    const WITHHOLD_FAIL = 'WithholdFail';
    /**
     * 场景:<b>【即将逾期】</b>
     */
    const WITHHOLD_WARMED = 'WithholdWarmed';
    /**
     * 场景:<b>【扣款失败生成逾期】</b>
     */
    const WITHHOLD_OVERDUE = 'WithholdOverdue';
    /**
     * 场景：<b>【提前还款】</b>
     */
    const REPAYMENT = 'Repayment';

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
    /**
     * 场景：<b>【退款成功】</b>
     */
    const REFUND_SUCCESS = 'RefundSuccess';

    /**
     * 场景：<b>【买断确认】</b>
     */
    const BUYOUT_CONFIRM = 'BuyoutConfirm';

    /**
     * 场景：<b>【买断支付】</b>
     */
    const BUYOUT_PAYMENT = 'BuyoutPayment';


}
