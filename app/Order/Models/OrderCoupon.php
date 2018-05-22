<?php

/**
 *
 *  订单设备列表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCoupon extends Model
{

    protected $table = 'order_coupon';

    protected $primaryKey='id';


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
    protected $fillable = ['order_no','coupon_no','coupon_id','discount_amount','coupon_type','coupon_name'];


}