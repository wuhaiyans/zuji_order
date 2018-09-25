<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActiveInvite;
use Illuminate\Support\Facades\DB;

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
     *      'uid'=>'' 【必须】用户id
     *      'activity_id'=>'' 【必须】 活动id
     *      'offset'=>'' 【可选】 偏移量
     *      'limit'=>'' 【可选】 显示条数
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

        if(isset($data['offset'])){
            $offset = $data['offset'];
        }
        if(isset($data['limit'])){
            $limit = $data['limit'];
        }
        if($limit>0){
            $data = ActiveInvite::query()->where($where)->offset($offset)->limit($limit)->orderBy("id","desc")->get()->toArray();
        }
        else{
            $data = ActiveInvite::query()->where($where)->orderBy("id","desc")->get()->toArray();
        }

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

    /**
     * 获取预定活动邀请人列表
     * @param $params
     * [
     *   'activity_id'  =>'',   //【必选】 int 活动ID
     *   'user_id'  =>'',   //【必选】 int 用户ID
     *   'page'         =>'',   //【可选】 int 页数
     *   'size'         =>''    //【可选】 int 每页数量
     * ]
     * @return array

     */
    public static  function getDestinePageList($param=array()){
        $page = empty($param['page']) || !isset($param['page']) ? 1 : $param['page'];
        $size = !empty($param['size']) && isset($param['size']) ? $param['size'] : config('web.pre_page_size');
        $whereArray[] = ['activity_id', '=', $param['activity_id']];
        $whereArray[] = ['uid', '=', $param['user_id']];

        $destineList =  DB::table('order_active_invite')
            ->select('order_active_invite.*')
            ->where($whereArray)
            ->orderBy('create_time', 'DESC')
            ->paginate($size,$columns = ['*'], $pageName = 'page', $page);

        if($destineList){
            return $destineList->toArray();
        }
        return [];
    }
}