<?php

/**
 *
 *  收入表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayIncome extends Model
{

    protected $table = 'order_pay_income';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['name','order_no','business_type','business_no','appid','channel','trade_no','out_trade_no','amount','create_time'];
}