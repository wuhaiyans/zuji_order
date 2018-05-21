<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Models;

class DeliveryGoodsImei extends Warehouse
{
    protected $table = 'zuji_delivery_goods_imei';

    public $timestamps = false;

    const STATUS_NO = 0; //无效
    const STATUS_YES = 1; //有效


    protected $fillable = ['delivery_no', 'serial_no', 'imei', 'apple_serial', 'status', 'create_time', 'status_time'];

    /**
     * @param $data
     * 存储
     */
//    public static function store($data)
//    {
//        $model = new self();
//
//        $model->create($data);
//
//        return $model;
//    }

    public static function listByDelivery($delivery_id)
    {
        return self::where(['delivery_no'=>$delivery_id, 'status'=>self::STATUS_YES])->all();
    }

}