<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:29
 */

namespace App\Warehouse\Models;

class ReceiveGoods extends Warehouse
{
    public $incrementing = false;
    protected $table = 'zuji_receive_goods';
    public $timestamps = false;

    /**
     * 收货单中设备状态
     */
    const STATUS_NONE = 0;//已取消
    const STATUS_INIT = 1;//待验收
    const STATUS_PART_RECEIVE = 2;//部分收货完成
    const STATUS_ALL_RECEIVE = 3;//全部收货完成
    const STATUS_PART_CHECK = 4;//部分检测完成
    const STATUS_ALL_CHECK = 5;//全部检测完成
    const STATUS_CONFIRM_RECEIVE = 6;//确认换货
    const STATUS_IN = 7;//确认入库


    /**
     * 检测结果
     */
    const CHECK_RESULT_INVALID  = 0;//无效
    const CHECK_RESULT_OK       = 1;//合格
    const CHECK_RESULT_FALSE    = 2;//不合格


    /**
     * @var array
     *
     * 可填充字段
     */
    protected $fillable = [
        'receive_no',
        'refund_no',
        //'serial_no',
        'goods_no',
        'quantity',
        'goods_no',
        'goods_name',
        'quantity_delivered',
        'status',
        'status_time',
        'check_time',
        'check_result',
        'check_result',
        'check_description',
        'check_price'
    ];

    /**
     * @param null $status
     * @return array|mixed|string
     * 状态列表
     */
    public static function status($status=null)
    {
        $st = [
            self::STATUS_NONE => '已取消',
            self::STATUS_INIT => '待验收',
            self::STATUS_PART_RECEIVE   => '部分收货完成',
            self::STATUS_ALL_RECEIVE    => '全部收货完成',
            self::STATUS_PART_CHECK     => '部分检测完成',
            self::STATUS_ALL_CHECK      => '全部检测完成',
            self::STATUS_CONFIRM_RECEIVE      => '确认换货',
            self::STATUS_IN      => '确认入库'
        ];

        if ($status === null) return $st;
        return isset($st[$status]) ? $st[$status] : '';
    }


    /**
     * @param null $type
     * @return array|mixed|string
     * 类型
     */
    public static function results($type=null)
    {
        $tp = [
            self::CHECK_RESULT_INVALID  => '无效',
            self::CHECK_RESULT_OK       => '合格',
            self::CHECK_RESULT_FALSE    => '不合格'
        ];

        if ($type === null) return $tp;

        return isset($tp[$type]) ? $tp[$type] : '';
    }


    /**
     * @return array|mixed|string
     * 取检测状态
     */
    public function getResult()
    {
        return self::results($this->check_result);
    }

    /**
     * @return array|mixed|string
     * 取收货状态
     */
    public function getStatus()
    {
        return self::status($this->status);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * 获取receive
     */
    public function receive()
    {
        return $this->belongsTo(\App\Warehouse\Models\Receive::class, 'receive_no');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * delivery_imeis表关联
     * 外键 delivery_no
     */
    public function imeis()
    {
        return $this->hasMany(\App\Warehouse\Models\ReceiveGoodsImei::class, 'goods_no');
    }
}