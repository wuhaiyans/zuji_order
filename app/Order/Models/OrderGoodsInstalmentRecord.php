<?php

/**
 *
 *  订单分期记录数据表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoodsInstalment extends Model
{

    protected $table = 'order_goods_instalment_record';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['instalment_id','type','payment_amount','payment_discount_amount','discount_type','discount_value','discount_name','status','create_time','update_time'];
}