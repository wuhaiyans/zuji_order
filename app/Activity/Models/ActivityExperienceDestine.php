<?php

namespace App\Activity\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityExperienceDestine extends Model
{
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    // Rest omitted for brevity

    protected $table = 'order_activity_experience_destine';

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
    protected $fillable = ['destine_no','activity_id','mobile','user_id','experience_id','zuqi','destine_status','destine_amount','pay_channel','app_id','create_time','update_time','pay_time','account_time','account_number','refund_remark'];

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