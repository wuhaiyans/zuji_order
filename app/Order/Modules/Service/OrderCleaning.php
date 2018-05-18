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

    /*
     * 订单清算列表
     * @param array $param
     * @return array
     */
    public static function getOrderCleaningList($param = array())
    {

        $orderCleanList = OrderClearingRepository::getOrderCleanList($param);
        return apiResponseArray(ApiStatus::CODE_0,$orderCleanList);

    }


    /*
     * 订单操作表
     * @param array $param
     * @return array
     */
    public static function cancelOrderClean($param = array())
    {
        $success= OrderClearingRepository::cancelOrderClean($param);
        return $success;

    }

    /**
     * 更新订单清算状态
     * @param $param
     * @return bool
     */
    public static function upOrderCleanStatus($param)
    {

        $success= OrderClearingRepository::upOrderCleanStatus($param);
        return $success;

    }


    /**
     * 插入订单清算
     * @param $param
     * @return bool|string
     */
    public static function createOrderClean($param)
    {

        $success= OrderClearingRepository::createOrderClean($param);
        return $success;


    }



    /**
     * 订单清算操作
     * @param $param
     * @return bool|string
     */
    public static function orderCleanOperate($param)
    {

        //查询清算表
        $orderCleanData =  OrderClearingRepository::getOrderCleanInfo($param);
        if (empty($orderCleanData)) return false;
        dd($orderCleanData);


        //发起清算 解押金，退租金
        $success= OrderClearingRepository::orderCleanOperate($param);
        return $success;


    }


}