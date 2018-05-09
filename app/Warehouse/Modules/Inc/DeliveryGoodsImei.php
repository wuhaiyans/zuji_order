<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Modules\Inc;

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
            $model->create_time =time();
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
     * 取消配货时，删除
     */
    public static function cancelMatch($delivery_no)
    {

       return self::where(['delivery_no'=>$delivery_no])->delete();

    }


    /**
     * @param $delivery_id
     * @param $imei
     * 删除
     */
    public static function del($delivery_no, $imei)
    {
        return self::where(['delivery_no'=>$delivery_no, 'imei'=>$imei])->delete();
    }


    /**
     * @param $delivery_id
     * @param $imei
     * 添加
     */
    public static function add($delivery_no, $imeis)
    {
        $time = time();

        if (!is_array($imeis)) {
            throw new \Exception('参数错误');
        }

        foreach ($imeis as $imei) {
            $model = new self();
            $model->delivery_no = $delivery_no;
            $model->imei = $imei['imei'];
            $model->status = self::STATUS_YES;
            $model->status_time = $time;
            $model->serial_no = $imei['serial_no'];
            $model->save();
        }

        return true;
    }

    public static function listByDelivery($delivery_id)
    {
        return self::where(['delivery_no'=>$delivery_id, 'status'=>self::STATUS_YES])->all();
    }

}