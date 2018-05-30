<?php

namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $table = 'order_log';

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
    protected $fillable = ['order_no','action','operator_id','operator_name','operator_type','msg','system_time'];

}