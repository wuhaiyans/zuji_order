<?php

/**
 *
 *  订单设备列表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderOverdueDeduction extends Model
{

    protected $table = 'order_overdue_deduction';

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
    protected $fillable = ['order_no','order_time','order_source','goods_name','zuqi_type','user_name','mobile','unpaid_amount','overdue_amount','deduction_status','visit_id','deduction_time','deduction_amount','create_time','update_time'];

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