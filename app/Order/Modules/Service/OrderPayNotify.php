<?php
/**
 * 订单支付回调方法
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Service;


use App\Http\Requests\Request;
use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Debug\Debug;

class OrderPayNotify
{
// 支付阶段完成时业务的回调配置
// 回调接收一个参数，关联数组类型：
// [
//		'business_type' => '',	// 业务类型
//		'business_no'	=> '',	// 业务编码
//		'status'		=> '',	// 支付状态  processing：处理中；success：支付完成
// ]
// 支付阶段分3个环节，一次是：直接支付 -> 代扣签约 -> 资金预授权
// 所有业务回调有可能收到两种通知：
//	1）status 为 processing
//		这种情况时
//		表示：直接支付环节已经完成，还有后续环节没有完成。
//		要求：如果这时要取消支付后，必须进行退款处理，然后才可以关闭业务。
//	2）status 为 success
// 格式： 键：业务类型；值：可调用的函数，类静态方法
    public function callback($params)
    {
        $businessType = $params['business_type'];
        $orderNo = $params['business_no']; //订单支付也就是订单编号
        $status = $params['status'];


        if ($status == "processing") {
            $b = OrderRepository::orderPayStatus($orderNo, OrderStatus::OrderPaying);
            if (!$b) {
                LogApi::notify("订单支付失败", $orderNo);
            }
        } else {
            $b = OrderRepository::orderPayStatus($orderNo, OrderStatus::OrderPayed);
            if (!$b) {
                LogApi::notify("订单支付失败", $orderNo);
            }
             $orderInfo = OrderRepository::getOrderInfo(['order_no' => $orderNo]);
            //发送支付成功短信
            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_PAY);
            $orderNoticeObj->notify();
            //发送支付宝推送消息
            //发送邮件 -----begin
            //        $data =[
            //            'subject'=>'用户已付款',
            //            'body'=>'订单编号：'.$order_info['order_no']."联系方式：".$order_info['mobile']." 请联系用户确认租用意向。",
            //            'address'=>[
            //                ['address' => EmailConfig::Service_Username]
            //            ],
            //        ];
            //
            //        $send =EmailConfig::system_send_email($data);
            //        if(!$send){
            //            Debug::error(Location::L_Trade, "发送邮件失败", $data);
            //        }
            //
            //        //发送邮件------end
        }

    }
}