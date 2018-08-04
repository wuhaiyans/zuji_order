<?php

/**
 *
 *  小程序 请求回调 记录表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMiniNotifyLog extends Model
{

    protected $table = 'order_mini_notify_log';

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
        'redis_key',
        'out_order_no',
        'zm_order_no',
        'out_trans_no',
        'alipay_fund_order_no',
        'pay_time',
        'pay_status',
        'pay_amount',
        'channel',
        'notify_app_id',
        'credit_privilege_amount',
        'order_create_time',
        'fund_type',
        'notify_app_id',
        'data_text',
    ];


}