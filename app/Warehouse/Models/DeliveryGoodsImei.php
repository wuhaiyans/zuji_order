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


    public static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

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


    /**
     * @param $delivery_id
     * @param $imei
     * 添加
     */
    public static function add($delivery_id, $imei)
    {
        $time = time();
        return self::updateOrCreate(
            ['delivery_id' => $delivery_id, 'imei' => $imei],
            ['status_time' => $time, 'status'=>self::STATUS_YES]
        );

//        $model = self::where(['delivery_id'=>$delivery_id, 'imei'=>$imei]);
//        if (!$model) {
//            $model = new self();
//            $model->delivery_id = $delivery_id;
//            $model->imei = $imei;
//            $model->create_time = $time;
//        }
//        $model->status_time = $time;
//        $model->status = self::STATUS_YES;

//        return $model->save();
    }

    public static function listByDelivery($delivery_id)
    {
        return self::where(['delivery_no'=>$delivery_id, 'status'=>self::STATUS_YES])->all();
    }

}