<?php

/**
 *
 *  小程序获取临时订单号接口保存请求信息
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMiniCreditRent extends Model
{

    protected $table = 'order_mini_credit_rent';

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
        'data',
    ];


}