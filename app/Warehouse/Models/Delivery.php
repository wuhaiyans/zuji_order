<?php

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Monolog\Handler\IFTTTHandler;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

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
    const UPDATED_AT = 'update_time';

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


    /**
     * 生成单号
     */
    public static function generateSerial()
    {
        return date('YmdHis').uniqid();
    }


    /**
     * 收货
     */
    public static function receive($delivery_id, $auto=false)
    {
        $model = self::findOrFail($delivery_id);
        $model->status = self::STATUS_RECEIVED;

        return $model->update();
    }

    /**
     * @param $order_no
     * 取消发货
     */
    public static function cancel($order_no)
    {
        $model = self::where('order_no', $order_no)->first();
        $model->status = self::STATUS_CANCEL;

        return$model->update();
    }

    /**
     * 修改物流
     */
    public static function logistics($delivery_id, $logistics_id, $logistics_no)
    {
        $model = self::findOrFail($delivery_id);

        $model->logistics_id = $logistics_id;
        $model->logistics_no = $logistics_no;

        return $model->save();
    }


    /**
     * @param $order_no
     * 发货
     */
    public static function send($order_no)
    {
        $model = self::where(['order_no'=> $order_no, 'status'=>self::STATUS_WAIT_SEND])->first();

        if (!$model) {
            throw new NotFoundResourceException($order_no . ' 订单号未找到');
        }

        $model->status = self::STATUS_SEND;
        $model->delivery_time = time();

        return $model->save();
    }


    /**
     * @param $delivery_id
     * 取消配货
     */
    public static function cancelMatch($delivery_id)
    {

    }

    /**
     * @param $delivery_id
     * 配货完成
     */
    public static function finishMatch($delivery_id)
    {

    }












}