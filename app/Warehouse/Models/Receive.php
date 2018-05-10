<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:17
 */

namespace App\Warehouse\Models;

class Receive extends Warehouse
{
    public $incrementing = false;
    protected $table = 'zuji_receive';
    protected $primaryKey = 'receive_no';
    public $timestamps = false;

    const STATUS_NONE = 0;//已取消
    const STATUS_INIT = 1;//待收货
    const STATUS_RECEIVED = 2;//已收货
    const STATUS_FINISH = 3;//检测完成

    const CHECK_RESULT_INVALID = 0;//无效
    const CHECK_RESULT_OK = 1;//全部合格
    const CHECK_RESULT_FALSE = 2;//有不合格

    /**
     * @param null $status
     * @return array|mixed|string
     * 状态
     */
    public static function status($status=null)
    {
        $st = [
            self::STATUS_NONE => '已取消',
            self::STATUS_INIT => '待收货',
            self::STATUS_RECEIVED => '已收货',
            self::STATUS_FINISH => '检测完成'
        ];


        if ($status === null) return $st;

        return isset($st[$status]) ? $st[$status] : '';

    }

    /**
     * @param null $type
     * @return array|mixed|string
     * 类型
     */
    public static function results($type=null)
    {
        $tp = [
            self::CHECK_RESULT_INVALID => '无效',
            self::CHECK_RESULT_OK       => '全合格',
            self::CHECK_RESULT_FALSE    => '有不合格'
        ];

        if ($type === null) return $tp;

        return isset($tp[$type]) ? $tp[$type] : '';
    }


    /**
     * @return array|mixed|string
     * 取检测状态
     */
    public function getResult()
    {
        return self::results($this->check_result);
    }

    /**
     * @return array|mixed|string
     * 取收货状态
     */
    public function getStatus()
    {
        return self::status($this->status);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * 取商品
     */
    public function goods()
    {
        return $this->hasMany(\ReceiveGoods::class, 'receive_no');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * 取imei
     */
    public function imeis()
    {
        return $this->hasMany(\ReceiveGoodsImei::class, 'receive_no');
    }


}