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
                    'payment_btn'   => '付款',
                    'cancel_btn'    => '取消'
                ],
                //支付中
                Inc\OrderStatus::OrderPaying => '',
                //已支付
                Inc\OrderStatus::OrderPayed => '',
                //备货中
                Inc\OrderStatus::OrderInStock => '',
                //已发货
                Inc\OrderStatus::OrderDeliveryed => [
                    'confirm_btn'   => '确认收货',
                    'logistics_btn' =>'查看物流',
                ],
                //租用中
                Inc\OrderStatus::OrderInService => [
                    'service_btn'=>'申请售后',
                    'prePay_btn'=>'提前还款',
                    'expiry_process'   => '到期处理',
                ],
                //已取消（未支付）
                Inc\OrderStatus::OrderCancel => '',
                //已关闭（已退款）
                Inc\OrderStatus::OrderClosedRefunded => '',
                 //已完成
                Inc\OrderStatus::OrderCompleted => '',
            ));


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
