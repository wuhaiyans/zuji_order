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
        'create_time',
        'compensate_amount',
        'goods_no',
        'imgs',
        'dingsun_type',
    ];

    //检测结果
    const RESULT_OK = 1;    //ok
    const RESULT_FALSE = 2; //检查有错

    //定损类型
    const DINGSUN_TYPE=[
        1=>'丢失',
        2=>'损坏',
        3=>'逾期'
    ];


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;
}