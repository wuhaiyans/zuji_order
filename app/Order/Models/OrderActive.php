<?php

/**
 *
 *  订单分期数据表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderActive extends Model
{

    protected $table = 'order_active';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['id','instalment_id','order_no','goods_no','appid','zuji_type','amount','realname','status'];
}