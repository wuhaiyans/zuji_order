<?php
/**
 * 邀请活动
 * @access public (访问修饰符)
 * @author limin <limin@huishoubao.com>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;


use App\Activity\Modules\Repository\InviteRepository;
use Illuminate\Http\Request;
use App\Lib\ApiStatus;


class InviteController extends Controller
{
    /*
     * 邀请计数
     * @param null
     * @return $data
     */
    public function numeration(Request $request){
        $request = $request->all();
        $params = $request['params'];
        $userInfo = $request['userinfo'];
        // 验证参数
        if(empty($params['code'])){
            return apiResponse([],ApiStatus::CODE_20001,"code必须");
        }
        if(empty($params['images'])){
            return apiResponse([],ApiStatus::CODE_20001,"images必须");
        }
        $activity_uid = $userInfo['uid'];
        //解密邀请码
        $code = "";
        $uid = "";
        $activity_id = "";
        
    }
    /*
     * 我的邀请
     * @param null
     * @return $data
     */
    public function myInvite(Request $request){
        $request = $request->all();
        $params = $request['params'];
        $userInfo = $request['userinfo'];
        if(empty($params['activity_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"activity_id必须");
        }
        $uid = $userInfo['uid'];
        $activity_id = $params['activity_id'];
        $data = InviteRepository::getList($uid,$activity_id);
        return apiResponse($data,ApiStatus::CODE_0);
    }

}