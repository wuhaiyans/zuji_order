<?php

namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderInsurance extends Model
{

    // Rest omitted for brevity

    protected $table = 'order_info_insurance';
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
    protected $fillable = ['order_no','goods_no','remark','type','create_time'];

}