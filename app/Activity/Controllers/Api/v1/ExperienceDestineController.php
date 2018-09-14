<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;



use App\Activity\Modules\Service\ExperienceDestineOperate;
use App\Http\Requests\Request;
use App\Lib\ApiStatus;

class ExperienceDestineController extends Controller
{
    /***
     * 活动预定支付接口
     * @author wuhaiyan
     * @param Request $request
     * $request['appid']
     * [
     *      'appid'	=> '',	            //【必选】int 渠道入口
     * ]
     * $request['params']
     * [
     *		'pay_channel_id'	=> '',	//【必选】int 支付支付渠道
     *		'experience_id'	=> '',	    //【必选】int 活动ID
     *		'return_url'	=> '',	    //【必选】int 前端回跳地址
     *      'extended_params'=>[        //【小程序支付必选】array 扩展参数
     *          "alipay_params"=>[      //支付宝扩展参数
     *                  "trade_type"=>"APP"
     *          ],
     *          "wechat_params"=>[      //微信扩展参数
     *                  "openid"=>"oBjc20uu9n0R_uv2yAzRA0YHSVIs",
     *                  "trade_type"=>"JSAPI"
     *          ]
     * ]
     * $request['userinfo']     //【必须】array 用户信息  - 转发接口获取
     * $userinfo [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'uid'=>1,   //【必须】string 用户ID
     *      'username'=>1, //【必须】string 用户名
     *      'ip'=>'',//【必须】string 客户端IP
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
   public function experienceDestine(Request $request){
       $params = $request->all();
       //获取appid
       $appid	   = $params['appid'];

       $userType   = isset($params['userinfo']['type'])?$params['userinfo']['type']:0;
       $userName   = isset($params['userinfo']['username'])?$params['userinfo']['username']:'';
       $userId     = isset($params['userinfo']['uid'])?$params['userinfo']['uid']:0;
       $userIp     = isset($params['userinfo']['ip'])?$params['userinfo']['ip']:'';

       $payChannelId =isset($params['params']['pay_channel_id'])?$params['params']['pay_channel_id']:0;

       $experienceId  = isset($params['params']['experience_id'])?$params['params']['experience_id']:0;

       $extendedParams= isset($params['params']['extended_params'])?$params['params']['extended_params']:[];

       $returnUrl =$params['params']['return_url'];



       //判断参数是否设置
       if(empty($appid) && $appid <1){
           return apiResponse([],ApiStatus::CODE_20001,"appid错误");
       }
       if($userType!=2){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
       }
       if($payChannelId <1){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付渠道]");
       }
       if($experienceId <1){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[活动ID错误]");
       }
      if(!isset($returnUrl)){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[return_url 未设置错误]");
       }

       if (redisIncr("destine_add_".$userId,5)>1) {
           return apiResponse([],ApiStatus::CODE_51001,'操作太快，请稍等重试');
       }

       $data =[
           'appid'=>$appid,
           'experience_id'=>$experienceId,
           'mobile'=>$userName,
           'user_id'=>$userId,  //增加用户ID
           'ip'=>$userIp,  //增加用户ID
           'pay_channel_id'=>$payChannelId,
           'return_url'=>$returnUrl,           //【必须】string 前端回跳地址
           'extended_params'=>$extendedParams,           //【必须】string 前端回跳地址
           'auth_token'=>$params['auth_token'],
       ];
       $res = ExperienceDestineOperate::create($data);
       if(!$res){
           return apiResponse([],ApiStatus::CODE_51001,get_msg());
       }

       return apiResponse($res,ApiStatus::CODE_0);

   }
    /***
     * 活动预定支付接口
     * @author wuhaiyan
     * @param Request $request
     * $request['appid']
     * [
     *      'appid'	=> '',	            //【必选】int 渠道入口
     * ]
     * $request['params']
     * [
     *		'experience_id'	=> '',	    //【必选】int 活动体验ID
     * ]
     * $request['userinfo']     //【必须】array 用户信息  - 转发接口获取
     * $userinfo [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'uid'=>1,   //【必须】string 用户ID
     *      'username'=>1, //【必须】string 用户名
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function experienceDestineQuery(Request $request){
        $params = $request->all();

        //获取appid
        $appid	   = $params['appid'];

        $userInfo   = isset($params['userinfo'])?$params['userinfo']:[];
        $userType   = isset($params['userinfo']['type'])?$params['userinfo']['type']:0;

        $experienceId  = isset($params['params']['experience_id'])?$params['params']['experience_id']:0;

        //判断参数是否设置
        if(empty($appid) && $appid <1){
            return apiResponse([],ApiStatus::CODE_20001,"appid错误");
        }
        if($userType!=2 && empty($userInfo)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
        }

        if($experienceId <1){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[活动ID错误]");
        }

        $data =[
            'experience_id'=>$experienceId,
            'user_id'=>$params['userinfo']['uid'],  //增加用户ID
        ];
        $res = ExperienceDestineOperate::experienceDestineQuery($data);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_51001,get_msg());
        }

        return apiResponse($res,ApiStatus::CODE_0);

    }

}