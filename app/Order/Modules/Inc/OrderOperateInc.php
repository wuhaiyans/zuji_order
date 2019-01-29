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
            'good_status_info'=>array(
                'unpay_giveback_status_name'     => '待用户赔付',
                'unpay_giveback_status_info'     =>  '用户尚未赔付，需赔付后解除资金预授权',
                'check_giveback_status_name'     =>  '待检测',
                'check_giveback_status_info'     =>  '查看功能是否正常，是否有磨损',
                'overdue_status_name'            =>  '已逾期',
                'overdue_status_info'            =>  '用户已逾期',
                'cancel_status_name'             =>  '已取消',
                'cancel_status_info'             =>  '用户已取消订单',
                'toshipped_status_name'          =>  '待发货',
                'toshipped_status_info'          =>  '需要核对用户的信息,确认用户的信息无误，并上传商品细节图',
                'rent_status_name'               =>  '租用中',
                'rent_status_info'               =>  '用户归还需检查相关零配件是否齐全',
                'unclean_account_status_name'    =>  '待出账',
                'unclean_account_status_info'    =>  '需核实用户是否支付完毕，对资金预授权进行解冻',
                'complete_status_name'           =>  '已完结',
                'complete_status_info'           =>  '当前订单已经完结',
                'unpay_status_name'              =>  '待支付',
                'unpay_status_info'              =>  '待用户支付押金以及租金，支付完成后方可发货',

            ),
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
                //已支付
                Inc\OrderStatus::OrderPayed => [
                    //付款
                    'payment_btn'   => false,
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
                    'zuji_agreement_btn' => false

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
                Inc\OrderStatus::OrderAbnormal => [
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
                    //'线下还机'
                    'offline_giveback_btn' => false,
                ],
                //支付中
                Inc\OrderStatus::OrderPaying => [
                    'return_visit_btn'   => false,
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
                    //'线下还机'
                    'offline_giveback_btn' => false,
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
                    //'线下还机'
                    'offline_giveback_btn' => false,

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
                    //'线下还机'
                    'offline_giveback_btn' => false,
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
                    //'线下还机'
                    'offline_giveback_btn' => false,
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
                    //'线下还机'
                    'offline_giveback_btn' => true,
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
                    //'线下还机'
                    'offline_giveback_btn' => false,
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
                    //'线下还机'
                    'offline_giveback_btn' => false,
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
                    //'线下还机'
                    'offline_giveback_btn' => false,
                ],
                //逾期关闭
                Inc\OrderStatus::OrderAbnormal => [
                    //回访按钮
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
                    //'线下还机'
                    'offline_giveback_btn' => false,
                ],
            ),
            //线下门店后台用户出现的按钮
            'offlineOrderBtn'=>array(
                //待支付
                Inc\OrderStatus::OrderWaitPaying => [
                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'确认还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => false,


                ],
                //支付中
                Inc\OrderStatus::OrderPaying => [

                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => false,

                ],
                //已支付
                Inc\OrderStatus::OrderPayed => [
                    //支付后取消
                    'cancel_pay_btn'    => true,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => true,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => false,
                ],
                //备货中
                Inc\OrderStatus::OrderInStock => [
                    //支付后取消
                    'cancel_pay_btn'    => true,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => true,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => false,
                ],
                //已发货
                Inc\OrderStatus::OrderDeliveryed => [

                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => true,
                ],
                //租用中
                Inc\OrderStatus::OrderInService => [

                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => true,
                    //发货详情
                    'shipping_details_btn'    => true,

                ],
                //已取消（未支付）
                Inc\OrderStatus::OrderCancel => [

                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => false,


                ],
                //已关闭（已退款）
                Inc\OrderStatus::OrderClosedRefunded => [

                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => false,

                ],
                //已完成
                Inc\OrderStatus::OrderCompleted => [

                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => false,
                    //发货详情
                    'shipping_details_btn'    => true,

                ],
                //逾期关闭
                Inc\OrderStatus::OrderAbnormal => [

                    //支付后取消
                    'cancel_pay_btn'    => false,
                    //'线下还机'
                    'offline_giveback_btn' => false,
                    //发货
                    'deliver_btn'    => false,
                    //检测
                    'check_btn'    => false,
                    //检测结果
                    'check_result_btn'    => false,
                    //出账
                    'clean_account_btn'    => false,
                    //用户协议
                    'user_agreement_btn'    => true,
                    //发货详情
                    'shipping_details_btn'    => true,

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
