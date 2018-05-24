<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:29
 */

namespace App\Warehouse\Models;


class ReceiveGoodsImei extends Warehouse
{
    public $incrementing = false;
    protected $table = 'zuji_receive_goods_imei';
    public $timestamps = false;

    /**
     * 状态；0；已取消；1：待收货；2：收货完成；3：待检测；4：检测完成
     */
    const STATUS_INVALID = 0;//无效
    const STATUS_WAIT_RECEIVE = 1;//待收
    const STATUS_RECEIVED   = 2;//收完成
    const STATUS_WAIT_CHECK = 3;//待检测
    const STATUS_CHECK_OVER = 4; //检测完成

    /**
     * 类型
     */
    const TYPE_APPLE    = 1; //苹果
    const TYPE_ANDROID  = 2;//安卓

    protected $fillable = [
        'receive_no',
        'serial_no',
        'imei',
        'check_price',
        'status',
        'create_time',
        'cancel_time',
        'cancel_remark',
        'type',
        'serial_number'//序列号,ios设备
    ];

    /**
     * @param null $status
     * @return array|mixed|string
     * 状态列表
     */
    public static function status($status=null)
    {
        $st = [
            self::STATUS_INVALID    => '无效',
            self::STATUS_ACTIVE     => '有效'
        ];

        if ($status === null) return $st;
        return isset($st[$status]) ? $st[$status] : '';
    }

    /**
     * @param null $type
     * @return array|mixed|string
     * 类型
     */
    public static function types($type=null)
    {
        $tp = [
            self::TYPE_APPLE  => '苹果',
            self::TYPE_ANDROID       => '安卓',
        ];

        if ($type === null) return $tp;

        return isset($tp[$type]) ? $tp[$type] : '';
    }


    /**
     * @return array|mixed|string
     * 取类型
     */
    public function getType()
    {
        return self::types($this->type);
    }

    /**
     * @return array|mixed|string
     * 取状态
     */
    public function getStatus()
    {
        return self::status($this->status);
    }


    public function receive()
    {
        return $this->belongsTo(\App\Warehouse\Models\Receive::class, 'receive_no');
    }


}