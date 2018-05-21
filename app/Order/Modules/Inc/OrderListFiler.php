<?php
/**
 * 订单列表筛选配置
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/5/18 0018
 * Time: 下午 2:38
 */
namespace App\Order\Modules\Inc;
use App\Lib\Channel\Channel;
use App\Order\Modules\Inc;
class OrderListFiler
{

    /**
     * 获取订单筛选项
     * Author: heaven
     * @return array
     */
    private static function getOrderState()
    {
       $channlistName =  Channel::getChannelListName(config('tripartite.Interior_Goods_Request_data'));
        return array(

                    'order_state'=>Inc\OrderStatus::getStatusType(),
                    'kw_type'=>array(
                            '1' => '订单编号',
                            '2' => '手机号',
                            '3' => '设备名称',
                        ),
                    'pay_type_list' =>Inc\PayInc::getPayList(),
                    'visit_type_list' =>Inc\OrderStatus::getVisitType(),
                    'appid_list' => $channlistName
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
        if (isset(self::getOrderState()[$incName])) {
            if ($id!=''){
                if (isset(self::getOrderState()[$incName][$id])) {
                    return self::getOrderState()[$incName][$id];
                } else {
                    return false;
                }

            } else {
                return self::getOrderState()[$incName];
            }
        } else {
            return self::getOrderState();
        }
    }













}