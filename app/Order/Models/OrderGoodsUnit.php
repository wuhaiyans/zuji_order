<?php

/**
 *
 *  订单商品扩展表数据
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoodsUnit extends Model
{

    protected $table = 'order_goods_unit';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['order_no','goods_no','user_id','unit','unit_value','begin_time','end_time'];
}