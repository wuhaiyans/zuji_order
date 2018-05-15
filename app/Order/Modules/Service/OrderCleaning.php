<?php
/**
 *    订单清算服务类
 *    author: heaven
 *    date : 2018-05-14
 */
namespace App\Order\Modules\Service;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\ApiStatus;


class OrderCleaning
{


    //订单清算详情
    public static function getOrderCleaningInfo($orderNo)
    {
        if ($orderNo)
       $orderCleanData =  OrderClearingRepository::getOrderCleanInfo($orderNo);
       if (!empty($orderCleanData) && isset($orderCleanData)) {



       }

        $order = array();
        if (empty($orderNo))   return apiResponse([],ApiStatus::CODE_32001,ApiStatus::$errCodes[ApiStatus::CODE_32001]);
        //查询订单和用户发货的数据
        $orderData =  OrderRepository::getOrderInfo(array('orderNo'=>$orderNo));
        if (empty($orderData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['order_info'] = $orderData;
        //订单商品列表相关的数据
        $goodsData =  OrderRepository::getGoodsListByOrderId(array('orderNo'=>$orderNo));
        if (empty($goodsData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['goods_info'] = $goodsData;
        //设备扩展信息表
        $goodsExtendData =  OrderRepository::getGoodsExtendInfo(array('orderNo'=>$orderNo));
        if (empty($goodsExtendData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['goods_extend_info'] = $goodsExtendData;
        return apiResponseArray(ApiStatus::CODE_0,$order);
//        return $orderData;

    }

    /**
     * 订单清算列表
     * @param array $param
     * @return array
     */
    public static function getOrderCleaningList($param = array())
    {
        //根据用户id查找订单列表

        $orderCleanList = OrderClearingRepository::getOrderCleanList($param);
        if (empty($orderCleanList)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        return apiResponseArray(ApiStatus::CODE_0,$orderCleanList);


    }


}