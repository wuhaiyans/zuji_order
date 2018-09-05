<?php

namespace App\Activity\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityGoodsAppointment extends Model
{
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    // Rest omitted for brevity

    protected $table = 'order_activity_goods_appointment';

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
    protected $fillable = ['spu_id','appointment_id','goods_status','create_time','update_time'];

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