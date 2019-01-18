<?php

/**
 *
 * 商品扩展表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoodsExtend extends Model
{

    protected $table = 'order_goods_extend';

    protected $primaryKey='id';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['order_no','goods_no','return_name','return_phone','return_address_value','create_time','update_time'];

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