<?php
/**
 * User: jinlin
 * Date: 2018/9/1
 * Time: 16:20
 */

namespace App\Warehouse\Models;

/**
 * Class Imei
 * @package App\Warehouse\Models
 */
class ImeiUpdateLog extends Warehouse
{
    /**
     * @var bool
     *
     * 主键为字符串型，自增需要关闭
     */
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'zuji_imei_update_log';

    protected $primaryKey='id';
    /**
     * 字段转义
     */
    const TABLE_NAME = [
        'brand'=>'品牌',
        'name'=>'手机型号',
        'imei'=>'设备IMEI',
        'color'=>'颜色',
        'business'=>'运营商',
        'storage'=>'存储容量',
        'quality'=>'成色',
    ];

    /**
     *
     * 可填充字段
     */
    protected $fillable = [
        'id', 'imei_id', 'table_name', 'before_value', 'after_value', 'update_time'
    ];


}