<?php

/**
 *
 *  小程序订单信息
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMini extends Model
{

    protected $table = 'order_mini_info';

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
    protected $fillable = [
        'order_no',
        'zm_order_no',
        'name',
        'transaction_id',
        'cert_no',
        'mobile',
        'house',
        'zm_grade',
        'credit_amount',
        'zm_risk',
        'zm_face',
        'create_time',
        'user_id',
        'channel_id',
        'overdue_time',
        'app_id',
    ];


}