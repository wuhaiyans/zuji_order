<?php

namespace App\Warehouse\Models;


class Delivery extends Warehouse
{
    //状态
    const STATUS_NONE = 0;
    const STATUS_INIT = 1;//待配货
    const STATUS_WAIT_SEND  = 2;//已配货 待发货
    const STATUS_SEND       = 3;//已发货 待签收
    const STATUS_RECEIVED   = 4;//签收完成
    const STATUS_REFUSE     = 5;//拒签
    const STATUS_CANCEL     = 6;//已取消

    public $incrementing = false;

//    $timestamps=false时，没有用
//    const CREATED_AT = 'create_time';
//    const UPDATED_AT = 'update_time';

    protected $table = 'zuji_delivery';

    protected $primaryKey='delivery_no';

    /**
     * @var array
     *
     * 可以直接插入表的数据
     */
    protected $fillable = [
        'delivery_no',
        'order_no',
        'logistics_id',
        'status_time',
        'app_id',
        'customer',
        'is_auto',
        'customer_mobile',
        'customer_address',
        'logistics_no',
        'status',
        'create_time',
        'delivery_time',
        'status_remark'
    ];

    /**
     * 不使用框架自动填充功能
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * delivery_goods表关联
     * 外键 delivery_no
     */
    public function goods()
    {
        return $this->hasMany(DeliveryGoods::class, 'delivery_no');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * delivery_imeis表关联
     * 外键 delivery_no
     */
    public function imeis()
    {
        return $this->hasMany(DeliveryGoodsImei::class, 'delivery_no');
    }


    /**
     * @param null $status 不传值或传null时，返回状态列表，否则返回对应状态值
     * @return array|mixed|string
     *
     * 获取状态列表，或状态值
     */
    public static function sta($status=null)
    {
        $st = [
            self::STATUS_NONE   => '已删除',
            self::STATUS_INIT   => '待配货',
            self::STATUS_WAIT_SEND  => '已配货 待发货',
            self::STATUS_SEND       => '已发货 待签收',
            self::STATUS_RECEIVED   => '签收完成',
            self::STATUS_REFUSE     => '拒签',
            self::STATUS_CANCEL     => '已取消'
        ];

        if ($status === null) return $st;

        return isset($st[$status]) ? $st[$status] : '';
    }


    /**
     * @return array|mixed|string
     * 取收货状态
     */
    public function getStatus()
    {
        return self::sta($this->status);
    }

}