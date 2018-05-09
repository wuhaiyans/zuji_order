<?php

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Illuminate\Support\Facades\DB;

class Delivery extends Model
{

    const STATUS_NONE = 0;
    const STATUS_INIT = 1;//待配货
    const STATUS_WAIT_SEND  = 2;//待发货
    const STATUS_SEND       = 3;//已发货 待签收
    const STATUS_RECEIVED   = 4;//签收完成
    const STATUS_REFUSE     = 5;//拒签
    const STATUS_CANCEL     = 6;//已取消



    const CREATED_AT = 'create_time';
//    const UPDATED_AT = 'update_time';

    // Rest omitted for brevity

    protected $table = 'zuji_delivery';

    protected $primaryKey='delivery_no';
    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp() {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     *
     * @param DateTime|int $value
     * @return DateTime|int
     */
    public function fromDateTime($value) {
        return $value;
    }

    public function goods()
    {
        return $this->hasMany(DeliveryGoods::class, 'delivery_no');
    }

    public function imeis()
    {
        return $this->hasMany(DeliveryGoodsImei::class, 'delivery_no');
    }


//
//    /**
//     * 生成单号
//     */
//    public static function generateSerial()
//    {
//        return date('YmdHis').uniqid();
//    }
//
//
//    /**
//     * 收货
//     */
//    public static function receive($delivery_id, $auto=false)
//    {
//        $model = self::findOrFail($delivery_id);
//        $model->status = self::STATUS_RECEIVED;
//
//        return $model->update();
//    }
//
//    /**
//     * @param $order_no
//     * 取消发货
//     */
//    public static function cancel($order_no)
//    {
//        $model = self::where('order_no', $order_no)->first();
//        $model->status = self::STATUS_CANCEL;
//
//        return$model->update();
//    }
//
//    /**
//     * @param $delivery_id
//     * 获取imeis列表
//     */
//    public static function imeis($delivery_id)
//    {
//        return DeliveryGoodsImei::listByDelivery($delivery_id);
//    }
//
//






}