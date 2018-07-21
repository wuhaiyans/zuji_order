<?php
/**
 * 订单列表可操作按钮
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/5/24 0018
 * Time: 下午 4:38
 */
namespace App\Order\Modules\Inc;
use App\Lib\Channel\Channel;
use App\Order\Modules\Inc;
class OrderOperateInc
{

    //长期收货后7天内出现

    /**
     * 获取订单列表可操作项
     * Author: heaven
     * @return array
     */
    private static function getOrderOperate()
    {
        return array(
            //客户端订单列表的可用操作
            'actState'=>array(
                //待支付
                Inc\OrderStatus::OrderWaitPaying => [
                    //付款
                    'payment_btn'   => true,
                    //取消
                    'cancel_btn'    => true,
                    //支付后的取消
                    'cancel_pay_btn'    => false,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => false


                ],
                //支付中
                Inc\OrderStatus::OrderPaying => [

                    //付款
                    'payment_btn'   => true,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => false,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => false
                ],
                //已支付
                Inc\OrderStatus::OrderPayed => [
                    //付款
                    'payment_btn'   => false,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => true,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => false
                ],
                //备货中
                Inc\OrderStatus::OrderInStock => [
                    //付款
                    'payment_btn'   => false,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => true,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => false
                ],
                //已发货
                Inc\OrderStatus::OrderDeliveryed => [
                    //付款
                    'payment_btn'   => false,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => false,
                    //确认收货
                    'confirm_btn'   => true,
                    //查看物流
                    'logistics_btn' => true,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => true

                ],
                //租用中
                Inc\OrderStatus::OrderInService => [
                    //付款
                    'payment_btn'   => false,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => false,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> true,
                    //提前还款
                    'prePay_btn'=>  true,
                    //到期处理
                    'expiry_process'  =>  true,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => true
                ],
                //已取消（未支付）
                Inc\OrderStatus::OrderCancel => [

                    //付款
                    'payment_btn'   => false,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => false,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => false

                ],
                //已关闭（已退款）
                Inc\OrderStatus::OrderClosedRefunded => [
                    //付款
                    'payment_btn'   => false,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => false,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => true

                ],
                 //已完成
                Inc\OrderStatus::OrderCompleted => [

                    //付款
                    'payment_btn'   => false,
                    //取消
                    'cancel_btn'    => false,
                    //支付后的取消
                    'cancel_pay_btn'    => false,
                    //确认收货
                    'confirm_btn'   => false,
                    //查看物流
                    'logistics_btn' => false,
                    //申请售后
                    'service_btn'=> false,
                    //提前还款
                    'prePay_btn'=>  false,
                    //到期处理
                    'expiry_process'  =>  false,
                    //提前买断
                    'ahead_buyout' => false,
                    //还机去支付
                    'giveback_topay' => false,
                    //'买断去支付'
                    'buyout_topay' => false,
                    //查看租机协议
                    'zuji_agreement_btn' => true
                ],
            ),
            //后台用户出现的按钮
            'adminActBtn'=>array(
                //待支付
                Inc\OrderStatus::OrderWaitPaying => [
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => false,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //确认收货
                    'confirm_receive' => false,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
                //支付中
                Inc\OrderStatus::OrderPaying => [
                    'return_visit_btn'   => false,
                    //取消订单
                    'refund_btn'   => false,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //确认收货
                    'confirm_receive' => false,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
                //已支付
                Inc\OrderStatus::OrderPayed => [
                    //回访
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => true,
                    //确认订单
                    'confirm_btn'   => true,
                    //修改收货信息
                    'modify_address_btn' => true,
                    //确认收货
                    'confirm_receive' => false,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                     //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,

                ],
                //备货中
                Inc\OrderStatus::OrderInStock => [
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => true,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //确认收货
                    'confirm_receive' => false,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
                //已发货
                Inc\OrderStatus::OrderDeliveryed => [
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => false,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //确认收货
                    'confirm_receive' => true,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
                //租用中
                Inc\OrderStatus::OrderInService => [
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => false,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //买断
                    'buy_off'       => true,
                    //保险操作
                    'Insurance'     => true,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
                //已取消（未支付）
                Inc\OrderStatus::OrderCancel => [
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => false,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //确认收货
                    'confirm_receive' => false,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
                //已关闭（已退款）
                Inc\OrderStatus::OrderClosedRefunded => [
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => false,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //确认收货
                    'confirm_receive' => false,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
                //已完成
                Inc\OrderStatus::OrderCompleted => [
                    'return_visit_btn'   => true,
                    //取消订单
                    'refund_btn'   => false,
                    //确认订单
                    'confirm_btn'   => false,
                    //修改收货信息
                    'modify_address_btn' => false,
                    //确认收货
                    'confirm_receive' => false,
                    //买断
                    'buy_off'       => false,
                    //保险操作
                    'Insurance'     => false,
                    //已出险
                    'alreadyInsurance' => false,
                    //'保险详情'
                    'insuranceDetail' => false,
                ],
            )


        );


    }



    /**
     * 订单相关的配置信息
     * Author: heaven
     * @param string $id 需要查找订单模块的key值
     * @param string $incName 验证订单模块名称
     * @return array|bool|mixed
     */
    public static function orderInc($id='',$incName='') {
        //订单状态
        if (isset(self::getOrderOperate()[$incName])) {
            if ($id!=''){
                if (isset(self::getOrderOperate()[$incName][$id])) {
                    return self::getOrderOperate()[$incName][$id];
                } else {
                    return false;
                }

            } else {
                return self::getOrderOperate()[$incName];
            }
        } else {
            return self::getOrderOperate();
        }
    }













}
