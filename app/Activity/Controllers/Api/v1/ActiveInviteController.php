<?php
/**
 * 邀请活动
 * @access public (访问修饰符)
 * @author limin <limin@huishoubao.com>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;


use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\ActiveInviteRepository;
use App\Activity\Modules\Repository\Activity\ExperienceDestine;
use App\Activity\Modules\Repository\ExperienceDestineRepository;
use App\Activity\Modules\Service\ExperienceDestineOperate;
use App\Lib\Common\LogApi;
use App\Lib\User\User;
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
        $requests = $request->all();
        $params = $requests['params'];
        $userInfo = $requests['userinfo'];

        // 验证参数
        if(empty($params['code'])){
            return apiResponse([],ApiStatus::CODE_20001,"code必须");
        }
        $invite_uid = $userInfo['uid'];
        $invite_mobile = $userInfo['username'];
        //解密邀请码获取用户id和活动id
        $codeNum = ExperienceDestineOperate::getInvitationCode($params['code']);
        $uid = $codeNum['user_id'];
        $activity_id = $codeNum['activity_id'];
        $registerTime = date("Y-m-d",$userInfo['register_time']);
        //获取邀请人信息
        $user = User::getUser($uid);
        if(!$user){
            return apiResponse([],ApiStatus::CODE_50001,"邀请用户错误！");
        }
        //验证该用户是否已邀请
        $checkStatus = ActiveInviteRepository::checkInviteUser($invite_uid,$activity_id);
        if(!$checkStatus){
            return apiResponse([],ApiStatus::CODE_50000,"已邀请");
        }
        //对比注册时间
        $nowTime = date("Y-m-d");
        if($nowTime != $registerTime){
            return apiResponse([],ApiStatus::CODE_50000,"该用户不是新注册用户");
        }
        //获预约活动信息
        $activityInfo = ExperienceDestineRepository::getUserExperience($uid,$activity_id);
        if($activityInfo['destine_status'] == DestineStatus::DestineReceive){
            return apiResponse([],ApiStatus::CODE_50000,"已领取活动礼品");
        }
        //检测邀请上限
        if($activityInfo['zuqi']>=30){
            return apiResponse([],ApiStatus::CODE_50000,"已超过最大邀请人数");
        }
        //更新邀请信息
        $data = [
            'activity_id'=>$activity_id,
            'uid'=>$uid,
            'mobile'=>$user['username'],
            'invite_uid'=>$invite_uid,
            'invite_mobile'=>$invite_mobile,
            'create_time'=>time()
        ];
        //获取微信授权信息
        $userWechat = User::getUserWechat($uid);
        if($userWechat){
            $data['openid'] = $userWechat['openid'];
        }
        $InviteWechat = User::getUserWechat($invite_uid);
        if($InviteWechat){
            $data['invite_openid'] = $InviteWechat['openid'];
            $data['images'] = $InviteWechat['headimgurl'];
        }
        //插入邀请信息
        $ret = ActiveInviteRepository::insertInvite($data);
        if(!$ret){
            return apiResponse($data,ApiStatus::CODE_5000,"失败");
        }
        //更新租期天数
        ExperienceDestine::upZuqi($uid,$activityInfo['experience_id']);
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
            $page = 1;
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
        $page = $page-1;
        $page = $page>=$sum?$sum:$page;
        $limit = $limit<50?$limit:10;
        $offset = $page*$limit;
        $array['offset'] = $offset;
        $array['limit'] = $limit;
        //获取邀请人信息
        $list = ActiveInviteRepository::getList($array);
        //获预约活动信息
        $activityInfo = ExperienceDestineRepository::getUserExperience($uid,$activity_id);
        $activityInfo['head_images'] = "";
        //获取微信授权登录信息
        $userWechat = User::getUserWechat($uid);
        if($userWechat){
            $activityInfo['head_images'] = $userWechat['headimgurl'];
        }
        $activityInfo['zuqi_day'] = $count;
        $activityInfo['zuqi'] -= $count;
        $data = [
            'activity' => $activityInfo,
            'count' => $count,
            'total_page' =>$sum,
            'data' =>$list
        ];
        if(empty($activityInfo['head_images'])){}
        return apiResponse($data,ApiStatus::CODE_0);
    }

}