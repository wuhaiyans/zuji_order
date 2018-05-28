<?php

/**
 *
 *  小程序确认订单回调 记录表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class MiniOrderRentNotify extends Model
{

    protected $table = 'mini_order_rent_notify';

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
        'notify_type',
        'out_order_no',
        'zm_order_no',
        'order_create_time',
        'fund_type',
        'credit_privilege_amount',
        'channel',
        'notify_app_id',
        'data_text',
    ];


}