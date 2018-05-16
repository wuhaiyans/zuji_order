<?php

namespace App\Warehouse\Models;


class CheckItems extends Warehouse
{
    protected $table = 'zuji_check_item';

    protected $primaryKey='id';

    protected $fillable = ['receive_no', 'serial_no', 'check_item','check_name',
        'check_description', 'check_result', 'create_time'];


    const RESULT_OK = 1;
    const RESULT_FALSE = 2;


    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;
}