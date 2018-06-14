<?php

/**
 *
 *  订单设备列表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGoods extends Model
{

    protected $table = 'order_goods';

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
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['order_no','goods_name','zuji_goods_id','zuji_goods_sn','goods_no','goods_thumb','prod_id','prod_no','brand_id','category_id','machine_id','user_id','quantity','goods_yajin','yajin','zuqi','zuqi_type','zujin','machine_value','chengse','discount_amount','coupon_amount','amount_after_discount','edition','business_key','business_no','market_price','price','specs','insurance','buyout_price','begin_time','end_time','weight','goods_status','create_time','update_time'];

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