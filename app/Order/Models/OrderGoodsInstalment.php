<?php

/**
 *
 *  订单分期数据表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoodsInstalment extends Model
{

    protected $table = 'order_goods_instalment';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['id','order_no','goods_no','user_id','term','day','times','original_amount','discount_amount','amount','payment_amount','payment_discount_amount','status','payment_time','update_time','remark','fail_num','unfreeze_status'];
}