<?php

namespace App\Warehouse\Models;

/**
 * Class CheckItems
 * @package App\Warehouse\Models
 *
 * 设备检测项
 */
class CheckItems extends Warehouse
{
    protected $table = 'zuji_check_item';

    protected $primaryKey='id';

    protected $fillable = [
        'receive_no',       //收货单号
        'serial_no',
        'check_item',       //检查项
        'check_name',       //检查名
        'check_description',//检查描述
        'check_result',
        'create_time'
    ];

    //检测结果
    const RESULT_OK = 1;    //ok
    const RESULT_FALSE = 2; //检查有错


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;
}