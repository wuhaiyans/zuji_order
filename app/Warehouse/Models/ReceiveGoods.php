<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:29
 */

namespace App\Warehouse\Models;

class ReceiveGoods extends Warehouse
{
    public $incrementing = false;
    protected $table = 'zuji_receive_goods';
    public $timestamps = false;

    const STATUS_NONE = 0;//已取消
    const STATUS_INIT = 1;//待验收
    const STATUS_PART_RECEIVE = 2;//部分收货完成
    const STATUS_ALL_RECEIVE = 3;//全部收货完成
    const STATUS_PART_CHECK = 4;//部分检测完成
    const STATUS_ALL_CHECK = 4;//全部检测完成


    //检测
    const CHECK_RESULT_INVALID  = 0;//无效
    const CHECK_RESULT_OK       = 1;//合格
    const CHECK_RESULT_FALSE    = 2;//不合格

    /**
     * @param null $status
     * @return array|mixed|string
     * 状态列表
     */
    public static function status($status=null)
    {
        $st = [
            self::STATUS_NONE => '已取消',
            self::STATUS_INIT => '待验收',
            self::STATUS_PART_RECEIVE   => '部分收货完成',
            self::STATUS_ALL_RECEIVE    => '全部收货完成',
            self::STATUS_PART_CHECK     => '部分检测完成',
            self::STATUS_ALL_CHECK      => '全部检测完成'
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
            self::CHECK_RESULT_INVALID  => '无效',
            self::CHECK_RESULT_OK       => '合格',
            self::CHECK_RESULT_FALSE    => '不合格'
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
}