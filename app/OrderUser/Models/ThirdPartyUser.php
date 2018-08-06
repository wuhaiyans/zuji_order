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
    const STATUS_ZHIFU = 1;//已支付
    const STATUS_FAHUO = 2;//已发货
    const STATUS_QIANSHOU = 3;//已签收
    const STATUS_ZUYONG = 4;//租用中
    const STATUS_TUIHUO = 5;//已退货
    const STATUS_WANCHENG = 6;//已完成
    const STATUS_NONE = 7;//已关闭

    //下单平台
    const PLATFORM_JD = 1;//京东
    const PLATFORM_TB = 2;//淘宝
    const PLATFORM_BZ = 3;//白租
    const PLATFORM_RR = 4;//人人租机

    //品牌
    const PINPAI_0      = 0;//无
    const PINPAI_MEITU  = 1;//美图
    const PINPAI_APPLE  = 2;//苹果
    const PINPAI_VIVO   = 3;//vivo
    const PINPAI_YIJIA  = 4;//一加
    const PINPAI_XIAOMI = 5;//小米
    const PINPAI_REFA   = 6;//REFA
    const PINPAI_TMALL  = 7;//天猫

    //成色
    const CHENGSE_0         = 0;//无
    const CHENGSE_ERSHOU    = 1;//二手
    const CHENGSE_QUANXIAN  = 2;//全新
    const CHENGSE_CIXIN     = 3;//次新

    //订单类型
    const TYPE_CHANGZU   = 1;//长租
    const TYPE_DUANZU    = 2;//短租

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
            self::STATUS_ZHIFU      => '已支付',
            self::STATUS_FAHUO      => '已发货',
            self::STATUS_QIANSHOU   => '已签收',
            self::STATUS_ZUYONG     => '租用中',
            self::STATUS_TUIHUO     => '已退货',
            self::STATUS_WANCHENG   => '已完成',
            self::STATUS_NONE       => '已关闭'
        ];

        if ($status === null) return $st;

        return isset($st[$status]) ? $st[$status] : '';
    }

    /**
     * 品牌转换
     *
     * @param null $status
     * @return array|mixed|string
     */
    public static function pinpai($status=null)
    {
        $st = [
            self::PINPAI_0      => '无',
            self::PINPAI_MEITU  => '美图',
            self::PINPAI_APPLE  => '苹果',
            self::PINPAI_VIVO   => 'vivo',
            self::PINPAI_YIJIA  => '一加',
            self::PINPAI_XIAOMI => '小米',
            self::PINPAI_REFA   => 'REFA',
            self::PINPAI_TMALL  => '天猫'
        ];

        if ($status === null) return $st;

        return isset($st[$status]) ? $st[$status] : '';
    }

    /**
     * 成色转换
     *
     * @param null $status
     * @return array|mixed|string
     */
    public static function chengse($status=null)
    {
        $cs = [
            self::CHENGSE_0         => '无',
            self::CHENGSE_ERSHOU    => '二手',
            self::CHENGSE_QUANXIAN  => '全新',
            self::CHENGSE_CIXIN     => '次新'
        ];

        if ($status === null) return $cs;

        return isset($cs[$status]) ? $cs[$status] : '';
    }

    /**
     * 类型转换
     *
     * @param null $status
     * @return array|mixed|string
     */
    public static function types($status=null)
    {
        $cz = [
            self::TYPE_CHANGZU   => '长租',
            self::TYPE_DUANZU    => '短租'
        ];

        if ($status === null) return $cz;

        return isset($cz[$status]) ? $cz[$status] : '';
    }
}