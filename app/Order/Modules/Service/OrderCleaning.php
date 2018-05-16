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
    public static function getOrderCleanInfo($param)
    {

       $orderCleanData =  OrderClearingRepository::getOrderCleanInfo($param);
        return apiResponseArray(ApiStatus::CODE_0,$orderCleanData);


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
        return apiResponseArray(ApiStatus::CODE_0,$orderCleanList);

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
        return apiResponseArray(ApiStatus::CODE_0,$orderCleanList);

    }


}