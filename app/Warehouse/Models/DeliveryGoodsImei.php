<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryGoodsImei extends Model
{
    protected $table = 'zuji_delivery_goods_imei';

    public $timestamps = false;

    const STATUS_NO = 0; //无效
    const STATUS_YES = 1; //有效



    /**
     * @param $data
     * 存储
     */
    public static function store($data)
    {
        $model = new self();

        $model->create($data);

        return $model;
    }


    /**
     * @param $delivery_id
     * @param $imei
     * 删除
     */
    public static function del($delivery_id, $imei)
    {

    }

    public static function listByDelivery($delivery_id)
    {
        return self::where(['delivery_no'=>$delivery_id, 'status'=>self::STATUS_YES])->all();
    }

}