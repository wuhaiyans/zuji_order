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
 */
class Imei extends Warehouse
{
    /**
     * @var bool
     *
     * 主键为字符串型，自增需要关闭
     */
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'zuji_imei';

    protected $primaryKey='imei';
    /**
     * imei状态
     */
    const STATUS_CNACEN = 0;//取消
    const STATUS_IN     = 1;//仓库中
    const STATUS_OUT    = 2;//出库

    /**
     *
     * 可填充字段
     */
    protected $fillable = ['imei', 'price', 'status'];
}