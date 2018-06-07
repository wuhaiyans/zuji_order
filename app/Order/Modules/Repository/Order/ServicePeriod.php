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
    /**
     *
     * @var OrderGoodsUnit
     */
    private $model = [];

    /**
     * 构造函数
     * @param array $data 订单原始数据
     */
    public function __construct( OrderGoodsUnit $model ) {
        $this->model = $model;
    }

    /**
     * 读取商品原始数据
     * @return array
     */
    public function getData():array{
        return $this->model->toArray();
    }

    //-+------------------------------------------------------------------------
    // | 静态方法
    //-+------------------------------------------------------------------------
    /**
     * 获取商品设备周期表
     * <p>当设备周期不存在时，抛出异常</p>
     * @param int   	$id		    ID
     * @param int		$lock		锁
     * @return \App\Order\Modules\Repository\Order\ServicePeriod
     * @return  bool
     */
    public static function getByGoodsId( int $id, int $lock=0 ) {
        $builder = OrderGoodsUnit::where([
            ['id', '=', $id],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $unit_info = $builder->first();
        if( !$unit_info ){
            return false;
        }
        return new ServicePeriod( $unit_info );
    }
    /**
     * 获取商品设备周期表
     * <p>当设备周期不存在时，抛出异常</p>
     * @param string   	$order_no		    订单编号
     * @param string   	$goods_no		    商品编号
     * @param int		$lock		锁
     * @return \App\Order\Modules\Repository\Order\Goods
     * @return  bool
     */
    public static function getByGoodsUnitNo( $order_no, $goods_no, int $lock=0 ) {
        $builder = OrderGoodsUnit::where([
            ['order_no', '=', $order_no],
            ['goods_no', '=', $goods_no],
        ])->orderBy('id','desc')->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $unit_info = $builder->first();
        if( !$unit_info ){
            return false;
        }
        return new ServicePeriod( $unit_info );
    }

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
