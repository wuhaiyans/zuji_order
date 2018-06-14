<?php

namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    // Rest omitted for brevity

    protected $table = 'order_info';

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
    protected $fillable = ['order_no','mobile','user_id','order_type','order_status','freeze_type','pay_type','zuqi_type','remark','order_amount','goods_yajin','discount_amount','order_yajin','order_insurance','coupon_amount','create_time','update_time','pay_time','confirm_time','delivery_time','appid_id','channel_id','receive_time','complete_time'];

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
//    public function save(array $options = [])
//    {
//        $query = $this->newQueryWithoutScopes();
//        if ($this->fireModelEvent('saving') === false) {
//            return false;
//        }
//        $dirty = $this->getDirty();
//        if(!empty($dirty['order_status'])){
//
//        }
//        return true;
//    }
}