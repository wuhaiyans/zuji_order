<?php

namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnLog extends Model
{
    protected $table = 'order_return_log';

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
    protected $fillable = ['business_type','business_status','create_time'];

}