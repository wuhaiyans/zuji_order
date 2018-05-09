<?php

/**
 *
 *  退换货
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{

    protected $table = 'order_return';

    protected $primaryKey='id';


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

}