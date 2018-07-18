<?php

/**
 *
 *  小程序发送 扣款 取消 完成请求信息记录表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMiniCreditPay extends Model
{

    protected $table = 'order_mini_credit_pay';

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
        'order_operate_type',
        'out_order_no',
        'zm_order_no',
        'out_trans_no',
        'remark',
        'pay_amount',
    ];


}