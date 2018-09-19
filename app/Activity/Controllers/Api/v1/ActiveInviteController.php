<?php
/**
 * 邀请活动
 * @access public (访问修饰符)
 * @author limin <limin@huishoubao.com>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;


use App\Activity\Modules\Repository\ActiveInviteRepository;
use App\Activity\Modules\Repository\Activity\ExperienceDestine;
use App\Activity\Modules\Repository\ExperienceDestineRepository;
use App\Activity\Modules\Service\ExperienceDestineOperate;
use App\Lib\Common\LogApi;
use Illuminate\Http\Request;
use App\Lib\ApiStatus;


class ActiveInviteController extends Controller
{
    /*
     * 邀请计数
     * @param null
     * @return $data
     */
    public function numeration(Request $request){
        //echo ExperienceDestineOperate::setInvitationCode(['experience_id'=>1,'user_id'=>1]);die;
        $request = $request->all();
        $params = $request['params'];
        $userInfo = $request['userinfo'];
        // 验证参数
        if(empty($params['code'])){
            return apiResponse([],ApiStatus::CODE_20001,"code必须");
        }
        if(!isset($params['images'])){
            return apiResponse([],ApiStatus::CODE_20001,"images必须");
        }
        $invite_uid = $userInfo['uid'];
        $invite_mobile = $userInfo['username'];
        //解密邀请码获取用户id和活动id
        $codeNum = ExperienceDestineOperate::getInvitationCode($params['code']);
        $uid = $codeNum['user_id'];
        $activity_id = $codeNum['experience_id'];
        //验证是否已邀请
        $checkStatus = ActiveInviteRepository::checkInviteUser($invite_uid,$activity_id);
        if(!$checkStatus){
            return apiResponse([],ApiStatus::CODE_50000,"已邀请");
        }
        //更新邀请信息
        $data = [
            'activity_id'=>$activity_id,
            'uid'=>$uid,
            'invite_uid'=>$invite_uid,
            'invite_mobile'=>$invite_mobile,
            'images'=>$params['images'],
            'create_time'=>time()
        ];
        $ret = ActiveInviteRepository::insertInvite($data);
        if(!$ret){
            LogApi::debug("预约邀请",$data);
        }
        ExperienceDestine::upZuqi($uid,$activity_id);
        return apiResponse([],ApiStatus::CODE_0,"邀请成功");
    }
    /*
     * 我的邀请
     * @param params array [
     *      ’page‘=>'' //页码
     *      ’limit‘=>'' //显示条数
     *      ’activity_id‘=>'' //活动id
     * ]
     * @return $data
     */
    public function myInvite(Request $request){
        $request = $request->all();
        $params = $request['params'];
        $userInfo = $request['userinfo'];
        if(empty($params['activity_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"activity_id必须");
        }
        if(!$params['page']){
            $page = 0;
        }else{
            $page = $params['page'];
        }
        if(!$params['limit']){
            $limit = 10;
        }else{
            $limit = $params['limit'];
        }
        $uid = $userInfo['uid'];
        $activity_id = $params['activity_id'];
        $array['uid'] = $uid;
        $array['activity_id'] = $activity_id;
        //获取邀请总数
        $count = ActiveInviteRepository::getCount($array);
        $sum = ceil($count/$limit);
        $page = $page>0?$page-1:$page;
        $page = $page>=$sum?$sum:$page-1;
        $limit = $limit<50?$limit:10;
        $offset = $page*$limit;
        $array['offset'] = $offset;
        $array['limit'] = $limit;
        //获取邀请人信息
        $list = ActiveInviteRepository::getList($array);
        //获预约活动信息
        $activityInfo = ExperienceDestineRepository::getUserExperience($uid,$activity_id);
        $data = [
            'activity' => $activityInfo,
            'count' => $count,
            'total_page' =>$sum,
            'data' =>$list
        ];
        return apiResponse($data,ApiStatus::CODE_0);
    }

}