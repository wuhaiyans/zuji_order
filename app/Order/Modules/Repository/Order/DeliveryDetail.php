<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;
use App\Order\Models\OrderDelivery;
use App\Order\Models\OrderGoodsDelivery;

/**
 * 发货明细
 * （IMEI，合同）
 */
class DeliveryDetail {
	
	/**
	 * 发货明细
	 * @param string $goods_no
	 * @return array|bool	array：发货明细（一维数组）；false：不存在
	 * [
	 * ]
	 */
	public static function getDetail( string $goods_no ){
		return false;
	}
	
	/**
	 * 发货明细列表
	 * @param string $order_no
	 * @return array	发货明细列表（二维数组）
	 * [
	 * ]
	 */
	public static function getDetailList( string $order_no ){
		return [];
	}

    /**
     * 生成订单发货单
     * @param array $deliveryData
     * [
     *  'order_no'=>'',//订单编号
     *  'logistics_id'=>''//物流渠道ID
     *  'logistics_no'=>''//物流单号
     * ]
     * @return bool
     */

	public static function addOrderDelivery(array $deliveryData):bool {
        $res = OrderDelivery::create($deliveryData);
        $id =$res->getQueueableId();
        if(!$id){
            return false;
        }
        return true;
    }

    /**
     * 生成发货单明细
     * @param $orderNo
     * @param $goods_info array 商品信息 【必须】 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     * ]
     * @return bool
     */

	public static function addGoodsDeliveryDetail(string $orderNo,array $goodsInfo):bool {
        foreach ($goodsInfo as $k=>$v){
            $data =[
                'order_no'=>$orderNo,
                'goods_no'=>$v['goods_no'],
                'imei1'=>isset($v['imei1'])?$v['imei1']:"",
                'imei2'=>isset($v['imei2'])?$v['imei2']:"",
                'imei3'=>isset($v['imei3'])?$v['imei3']:"",
                'serial_number'=>$v['serial_number'] ? $v['serial_number'] : '',
                'status'=>0,
            ];
            $res =OrderGoodsDelivery::create($data);
            $id =$res->getQueueableId();
            if(!$id){
                return false;
            }
        }
        return true;
    }
	
}
