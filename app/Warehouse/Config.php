<?php
/**
 * User: wansq
 * Date: 2018/5/15
 * Time: 10:20
 */


namespace App\Warehouse;

class Config
{
    static $check_items = [
        'screen' => '屏幕',
        'battery'=> '电池',
        'system' => '系统'
    ];

    /**
     * @var array
     * 快递公司
     */
    static $logistics = [
        '1' => '顺丰',
        '2' => '中通',
        '3' => '圆通',
        '100' => '其它'
    ];
}