<?php

/**
 *
 *  订单设备列表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRelet extends Model
{
    //状态
    const STATUS1 = 1;//创建
    const STATUS2 = 2;//完成
    const STATUS3 = 3;//取消

    protected $table = 'order_relet';

    protected $primaryKey='id';

    /**
     * 指定是否模型应该被戳记时间。
     *
     * @var bool
     */
    public $timestamps = false;

    //状态转换成汉字
    public static function statusName($type = null)
    {
        $t = [
            self::STATUS1 => '创建',
            self::STATUS2 => '完成',
            self::STATUS3 => '取消'
        ];

        if ($type === null) {
            return $t;
        }

        return isset($t[$type]) ? $t[$type] : '';
    }

}