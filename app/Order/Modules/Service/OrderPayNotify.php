<?php
/**
 * 订单支付回调方法
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Service;


use App\Common\JobQueueApi;
use App\Http\Requests\Request;
use App\Lib\Common\LogApi;
use App\Lib\PublicInc;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\OrderLogRepository;
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
    public static function callback($params)
    {
        $businessType = $params['business_type'];
        $orderNo = $params['business_no']; //订单支付也就是订单编号
        $status = $params['status'];
        $order = Order::getByNo($orderNo);
        if(!$order){
            LogApi::alert("OrderPay-订单信息不存在:".$orderNo,[],[config('web.order_warning_user')]);
            LogApi::notify("订单信息不存在：", $orderNo);
            return false;
        }
        $b =$order->setPayStatus($params);
        if(!$b){
            LogApi::alert("OrderPay-更新订单状态失败:".$orderNo,[],[config('web.order_warning_user')]);
            LogApi::notify("订单支付失败", $orderNo);
            return false;
        }

        $orderInfo =$order->getData();

        $b =\App\Lib\Common\JobQueueApi::cancel(config('app.env')."OrderCancel_".$orderNo);
        if($status =="success"){
            //发送支付成功短信
            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_PAY);
            $orderNoticeObj->notify();

            //推送到区块链
            $b =OrderBlock::orderPushBlock($orderNo,OrderBlock::OrderPayed);
            LogApi::info("OrderPay-addOrderBlock:".$orderNo."-".$b);
            if($b==100){
                LogApi::alert("OrderPay-addOrderBlock:".$orderNo."-".$b,[],[config('web.order_warning_user')]);
            }

            //增加操作日志
            OrderLogRepository::add($orderInfo['user_id'],$orderInfo['mobile'],\App\Lib\PublicInc::Type_User,$orderInfo['order_no'],"支付","支付成功");
            //如果线下订单 订单状态直接转为 已确认待发货状态
            if($orderInfo['order_type'] == OrderStatus::orderStoreService){
                //调用确认订单接口
                $data=[
                        'order_no'  => $orderNo, //【必须】string 订单编号
                        'remark'=>'线下订单自动待发货',      //【必须】string 备注
                        'userinfo '=>[
                            'type'=>PublicInc::Type_System,     //【必须】int 用户类型:1管理员，2用户,3系统，4线下,
                            'user_id'=>1,   //【必须】int用户ID
                            'user_name'=>'系统', //【必须】string用户名
                        ]
                ];
                $b = OrderOperate::confirmOrder($data);
                if(!$b){
                    LogApi::error("OrderPay-storeConfirm:".$orderNo,$data);
                }
            }


        }
        LogApi::notify("订单支付成功：". $orderNo);
        return true;
    }
}