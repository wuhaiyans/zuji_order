<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:29
 */

namespace App\Warehouse\Models;

class ReceiveGoodsImei extends \Illuminate\Database\Eloquent\Model
{

    public $incrementing = false;
    protected $table = 'zuji_receive_goods_imei';
    public $timestamps = false;

    const STATUS_INVALID = 0;//无效
    const STATUS_ACTIVE = 1;//有效

    const TYPE_APPLE = 1; //苹果
    const TYPE_ANDROID = 2;//安卓

    /**
     * @param null $status
     * @return array|mixed|string
     * 状态列表
     */
    public static function status($status=null)
    {
        $st = [
            self::STATUS_INVALID    => '无效',
            self::STATUS_ACTIVE     => '有效'
        ];

        if ($status === null) return $st;
        return isset($st[$status]) ? $st[$status] : '';
    }


    /**
     * @param null $type
     * @return array|mixed|string
     * 类型
     */
    public static function types($type=null)
    {
        $tp = [
            self::TYPE_APPLE  => '苹果',
            self::TYPE_ANDROID       => '安卓',
        ];

        if ($type === null) return $tp;

        return isset($tp[$type]) ? $tp[$type] : '';
    }


    /**
     * @return array|mixed|string
     * 取类型
     */
    public function getType()
    {
        return self::types($this->type);
    }

    /**
     * @return array|mixed|string
     * 取状态
     */
    public function getStatus()
    {
        return self::status($this->status);
    }


}