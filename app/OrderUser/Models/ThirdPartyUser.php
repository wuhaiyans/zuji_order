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
    const STATUS_INIT = 1;//已支付
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

    /**
     * 下单平台转换
     *
     * @param null $status
     * @return array|mixed|string
     */
    public static function platform($status=null)
    {
        $pl = [
            self::PLATFORM_JD   => '京东',
            self::PLATFORM_TB   => '淘宝',
            self::PLATFORM_BZ   => '白租',
            self::PLATFORM_RR   => '人人租机'
        ];

        if ($status === null) return $pl;

        return isset($pl[$status]) ? $pl[$status] : '';
    }

    /**
     * 订单状态转换
     *
     * @param null $status
     * @return array|mixed|string
     */
    public static function sta($status=null)
    {
        $st = [
            self::STATUS_INIT   => '已支付',
            self::STATUS_ZUYONG => '租用中',
            self::STATUS_NONE   => '已关闭'
        ];

        if ($status === null) return $st;

        return isset($st[$status]) ? $st[$status] : '';
    }
}