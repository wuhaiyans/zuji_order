<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGoodsUnit;

class OrderGoodsUnitRepository{
    public function __construct()
    {
    }

    /**
     * @param $data
     *      'order_no'=>'required', //订单编号
            'goods_no'=>'required',//商品编号
            'user_id'=>'required',//用户ID
            'unit'=>'required',//租期单位
            'unit_value'=>'required',//租期值
            'begin_time'=>'required',//开始时间
            'end_time'=>'required',//结束时间
    ]);
     * @return bool|mixed
     */
    public static function add($data){
        $data =filter_array($data,[
            'order_no'=>'required',
            'goods_no'=>'required',
            'user_id'=>'required',
            'unit'=>'required',
            'unit_value'=>'required',
            'begin_time'=>'required',
            'end_time'=>'required',
        ]);
        if(count($data)!=7){
            return false;
        }
        $info =OrderGoodsUnit::create($data);
        return $info->getQueueableId();
    }

    //获取商品租期信息
    public static function getGoodsUnitInfo($goods_no){
        if (empty($goods_no)) return false;
        $result =  OrderGoodsUnit::query()->where([
            ['goods_no', '=', $goods_no],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }
}