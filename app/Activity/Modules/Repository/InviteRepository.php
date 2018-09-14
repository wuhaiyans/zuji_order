<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\Invite;

class InviteRepository
{
    /*
     * 邀请计数
     * @param $uid 用户id
     * @param $activity_id 活动id
     * @return $data
     */
    public static function getList($uid,$activity_id){
        $where = [
            ['uid','=',$uid],
            ['activity_id','=',$activity_id]
        ];
        $data = Invite::query()->where($where)->get()->toArray();
        return $data;
    }
}