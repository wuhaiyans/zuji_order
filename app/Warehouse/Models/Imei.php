<?php
/**
 * User: wansq
 * Date: 2018/5/14
 * Time: 10:27
 */

namespace App\Warehouse\Models;

/**
 * Class Imei
 * @package App\Warehouse\Models
 *
 */



class Imei extends Warehouse
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'zuji_imei';

    protected $primaryKey='imei';

    const STATUS_CNACEN = 0;//取消
    const STATUS_IN     = 1;//仓库中
    const STATUS_OUT    = 2;//出库

    protected $fillable = ['imei', 'price', 'status'];
}