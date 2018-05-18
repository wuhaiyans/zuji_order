<?php

/**
 *
 *  订单商品扩展表数据
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoodExtend extends Model
{

    protected $table = 'order_goods_extend';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['order_no','good_id','good_no','imei1','imei2','imei3','serial_number'];
}