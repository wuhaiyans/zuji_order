<?php

/**
 *
 *  订单分期备注数据表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoodsInstalmentRemark extends Model
{

    protected $table = 'order_goods_instalment_remark';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;


    // 可以被批量赋值的属性。
    protected $fillable = ['instalment_id','contact_status','remark','create_time'];
}