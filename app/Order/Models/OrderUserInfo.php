<?php

/**
 *
 *  下单地址用户相关信息
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderUserInfo extends Model
{

    protected $table = 'order_userinfo';

    protected $primaryKey='id';

    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    
    public $timestamps = false;


}