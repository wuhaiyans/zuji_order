<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:17
 */

namespace App\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Monolog\Handler\IFTTTHandler;


/**
 * Class Receive
 * @package App\Warehouse\Models
 *
 * 收货单
 */
class Receive extends Warehouse
{
    /**
     * 主键为字符串，关闭自增
     */
    public $incrementing = false;
    protected $table = 'zuji_receive';
    protected $primaryKey = 'receive_no';
    public $timestamps = false;

    /**
     * 收货单状态
     */
    const STATUS_CANCEL = 0;//已取消
    const STATUS_INIT = 1;//待收货
    const STATUS_RECEIVED = 2;//已收货
    const STATUS_FINISH = 3;//检测完成
    const STATUS_CONFIRM_RECEIVE = 4;//确认换货

    /**
     * 设备检查状态
     */
    const CHECK_RESULT_INVALID = 0;//无效
    const CHECK_RESULT_OK = 1;//全部合格
    const CHECK_RESULT_PART = 2;//有不合格

    /**
     * 收货类型
     */
    const TYPE_BACK = 1;//还
    const TYPE_RETURN = 2;//退
    const TYPE_EXCHANGE = 3;//换

    protected $fillable = [
        'receive_no',
        'order_no',
        'logistics_id',
        'logistics_no',
        'type',
        'business_key',
        'customer',
        'customer_mobile',
        'customer_address',
        'status',
        'status_time',
        'create_time',
        'app_id',
        'receive_type',
        'receive_time',
        'check_time',
        'check_result',
        'check_description'
    ];

    /**
     * @param null $status
     * @return array|mixed|string
     * 状态
     */
    public static function status($status=null)
    {
        $st = [
            self::STATUS_CANCEL => '已取消',
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
     * @param null $type
     * @return array|mixed|string
     * 归还属性
     */
    public static function types($type=null)
    {
        $tp = [
            self::TYPE_BACK     => '还机用户',
            self::TYPE_RETURN   => '退货用户',
            self::TYPE_EXCHANGE => '换货用户'
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
     * 取关联商品
     */
    public function goods()
    {
        return $this->hasMany(\App\Warehouse\Models\ReceiveGoods::class, 'receive_no');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * 取关联imei
     */
    public function imeis()
    {
        return $this->hasMany(\App\Warehouse\Models\ReceiveGoodsImei::class, 'receive_no');
    }

    /**
     * @return bool
     *
     * 更新检验结果
     */
    public function updateCheck()
    {
        $imeis = $this->imeis;
        $count = count($imeis);
        $checkOk = 0;
        foreach ($imeis as $imei) {
            if ($imei->check_result == ReceiveGoods::CHECK_RESULT_OK) {
                $checkOk++;
            }
        }

        $this->check_result = $checkOk < $count ? self::CHECK_RESULT_PART : self::CHECK_RESULT_OK;
        return $this->save();
    }

}