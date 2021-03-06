<?php

/**
 * 日志表
 *
 *  User: wangjinlin
 */
namespace App\Warehouse\Models;

class DeliveryLog extends Warehouse
{
    protected $table = 'zuji_delivery_log';

    public $timestamps = false;

    /**
     * @var array
     *
     * 可填充字段
     */
    protected $fillable = [
        'delivery_no',
        'description',
        'serial_no',
        'create_time'
    ];
}