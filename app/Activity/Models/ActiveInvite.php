<?php

namespace App\Activity\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveInvite extends Model
{
    const CREATED_AT = 'create_time';

    // Rest omitted for brevity

    protected $table = 'order_active_invite';

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
    protected $fillable = [
        'id', //主键id
        'activity_id', //活动id
        'uid', //用户id
        'mobile', //用户手机号
        'openid', //微信用户id
        'invite_uid', //受邀用户id
        'invite_mobile', //受邀用户手机号
        'invite_openid', //受邀微信用户id
        'images', //受邀用户头像
        'create_time', //创建时间
    ];

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