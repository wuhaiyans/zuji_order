<?php

/**
 *
 *  订单分期数据表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderInstalment extends Model
{

    protected $table = 'order_instalment';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['agreement_no','order_no','goods_no','term','times','amount','discount_amount','status','payment_time','trade_no','out_trade_no','update_time','remark','fail_num','unfreeze_status'];
}