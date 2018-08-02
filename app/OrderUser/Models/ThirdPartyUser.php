<?php

/**
 * 第三方平台下单用户
 *
 *  User: wangjinlin
 */
namespace App\OrderUser\Models;

class ThirdPartyUser extends OrderUser
{
    //状态
    const STATUS_INIT = 1;//待配货
    const STATUS_ZUYONG = 2;//租用中
    const STATUS_NONE = 3;//已关闭

    const PLATFORM_JD = 1;//京东
    const PLATFORM_TB = 2;//淘宝
    const PLATFORM_BZ = 3;//白租
    const PLATFORM_RR = 4;//人人租机

    protected $table = 'third_party_user';

    protected $primaryKey='id';

    public $timestamps = false;

    /**
     * @var array
     *
     * 可填充字段
     */
    protected $fillable = [
        'phone',
        'consignee',
        'shipping_address',
        'status',
        'platform',
        'start_time',
        'end_time',
        'user_name',
        'identity',
        'order_no',
        'imei',
        'remarks'
    ];
}