<?php

/**
 *
 *  订单短信发送记录表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSmsLog extends Model
{

    protected $table = 'order_sms_log';

    protected $primaryKey='id';



    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['mobile','template','success','params','result','send_time'];

    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp() {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     *
     * @param DateTime|int $value
     * @return DateTime|int
     */
    public function fromDateTime($value) {
        return $value;
    }

}