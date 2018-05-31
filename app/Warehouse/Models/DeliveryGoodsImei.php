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

    /**
     * @var array
     *
     * 可填充字段
     */
    protected $fillable = [
        'delivery_no',
        'serial_no',
        'imei',
        'goods_no',
        'apple_serial', //苹果手机序列号
        'status',
        'create_time',
        'status_time'
    ];


    /**
     * @param $delivery_id
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     *
     * 根据delivery_id查找goods_imeis
     */
    public static function listByDelivery($delivery_id)
    {
        return self::where(['delivery_no'=>$delivery_id, 'status'=>self::STATUS_YES])->all();
    }

}