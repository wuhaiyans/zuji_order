<?php

namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsLog extends Model
{
    protected $table = 'goods_log';

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
    protected $fillable = ['order_no','action','operator_id','operator_name','operator_type','msg','create_time','business_key','business_no','goods_no',];

}