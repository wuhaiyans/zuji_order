<?php

/**
 * 日志表
 *
 *  User: wangjinlin
 */
namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryLog extends Model
{

    protected $table = 'zuji_delivery_log';

    public $timestamps = false;
//    protected $primaryKey='id';

    protected $fillable = ['delivery_no', 'description', 'serial_no','create_time'];

}