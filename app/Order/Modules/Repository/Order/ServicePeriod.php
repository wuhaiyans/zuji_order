<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;
use App\Order\Models\OrderGoodsUnit;

/**
 * Description of ServicePeriod
 *
 * @author Administrator
 */
class ServicePeriod {
	//put your code here
    /**
     * 生成商品服务周期表
     * @param array $unitData
     * [
     *   'order_no'=>'',//订单编号
     *   'goods_no'=>'',//商品编号
     *   'user_id'=>'',//用户ID
     *   'unit'=>'',//租期类型
     *   'unit_value'=>'',// 租期
     *   'begin_time'=>'',//服务开始时间
     *   'end_time'=>'',//服务结束时间
     * ]
     * @return bool
     */

    public static function createService(array $unitData):bool {

        if(count($unitData)!=7){
            return false;
        }
        $res =OrderGoodsUnit::create($unitData);
        $id =$res->getQueueableId();
        if(!$id){
            return false;
        }
        return true;
    }
}
