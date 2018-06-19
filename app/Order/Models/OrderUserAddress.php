<?php

/**
 *
 *  下单地址用户相关信息
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderUserAddress extends Model
{

    protected $table = 'order_user_address';

    protected $primaryKey='id';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

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
    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['order_no','consignee_mobile','name','province_id','city_id','area_id','address_info','create_time','update_time'];


}