<?php

/**
 *
 *  订单设备列表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoods extends Model
{

    protected $table = 'order_goods';

    protected $primaryKey='id';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = true;

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

}