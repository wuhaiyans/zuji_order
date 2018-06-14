<?php

/**
 *
 *  退换货
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{

    protected $table = 'order_return';

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

    protected $fillable = ['refund_no','old_refund_id','out_refund_no','user_id','business_key','pay_amount','refund_amount','goods_no','status','create_time','update_time','order_no'];

}