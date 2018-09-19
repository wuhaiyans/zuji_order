<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActiveInvite;

class ActiveInviteRepository
{
    /*
     * 邀请人数总数
     * @param $data array [
     *      'uid'=>'' //用户id
     *      'activity_id'=>'' //活动id
     * ]
     * @return $data
     */
    public static function getCount($data){
        if(!$data['uid']){
            return false;
        }
        if(!$data['activity_id']){
            return false;
        }
        $where[] = ['uid','=',$data['uid']];
        $where[] = ['activity_id','=',$data['activity_id']];
        $count = ActiveInvite::query()->where($where)->count();
        return $count;
    }
    /*
     * 邀请人数信息
     * @param $data array [
     *      'uid'=>'' //用户id
     *      'activity_id'=>'' //活动id
     *      'offset'=>'' //偏移量
     *      'limit'=>'' //显示条数
     * ]
     * @return $data
     */
    public static function getList($data){
        $where = [];
        if(!$data['uid']){
            return false;
        }
        if(!$data['activity_id']){
            return false;
        }
        $where[] = ['uid','=',$data['uid']];
        $where[] = ['activity_id','=',$data['activity_id']];

        $offset = "";
        $limit = "";

        if($data['offset']){
            $offset = $data['offset'];
        }
        if($data['limit']){
            $limit = $data['limit'];
        }
        $data = ActiveInvite::query()->where($where)->offset($offset)->limit($limit)->get()->toArray();
        return $data;
    }
    /*
     * 检验受邀用户
     * @param $uid 用户id
     * @param $activity_id 活动id
     * @return $data
     */
    public static function checkInviteUser($uid,$activity_id){
        $where = [
            ['invite_uid','=',$uid],
            ['activity_id','=',$activity_id]
        ];
        $data = ActiveInvite::query()->where($where)->first();
        if($data){
            return false;
        }
        return true;
    }
    /*
     * 新增邀请人
     * @param $data
     * @return bool
     */
    public static function insertInvite($data){
        if(!$data){
            return false;
        }
        $ret = ActiveInvite::insert($data);
        return $ret;
    }
}