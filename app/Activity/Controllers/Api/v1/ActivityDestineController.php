<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;



use App\Activity\Modules\Service\ActivityDestineOperate;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;

class ActivityDestineController extends Controller
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
     *		'pay_type'	=> '',	        //【必选】int 支付方式
     *		'activity_id'	=> '',	    //【必选】int 活动ID
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
   public function destine(Request $request){
       $params = $request->all();
       //获取appid
       $appid	   = $params['appid'];

       $userInfo   = isset($params['userinfo'])?$params['userinfo']:[];
       $userType   = isset($params['userinfo']['type'])?$params['userinfo']['type']:0;

       $payType	   = isset($params['params']['pay_type'])?$params['params']['pay_type']:0;//支付方式ID

       $payChannelId =isset($params['params']['pay_channel_id'])?$params['params']['pay_channel_id']:0;

       $activityId  = isset($params['params']['activity_id'])?$params['params']['activity_id']:0;

       $extendedParams= isset($params['params']['extended_params'])?$params['params']['extended_params']:[];

       $returnUrl =$params['params']['return_url'];

       //判断参数是否设置
//       if(empty($appid) && $appid <1){
//           return apiResponse([],ApiStatus::CODE_20001,"appid错误");
//       }
//       if($userType!=2 && empty($userInfo)){
//           return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
//       }
//       if($payType <1){
//           return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付方式错误]");
//       }
//       if($payChannelId <1){
//           return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付渠道]");
//       }
//       if($activityId <1){
//           return apiResponse([],ApiStatus::CODE_20001,"参数错误[活动ID错误]");
//       }
//      if(!isset($returnUrl)){
//           return apiResponse([],ApiStatus::CODE_20001,"参数错误[return_url 未设置错误]");
//       }

       $data =[
           'appid'=>$appid,
           'pay_type'=>$payType,
           'activity_id'=>$activityId,
           'mobile'=>'17600224881',//$params['userinfo']['username'],
           'user_id'=>7994,//$params['userinfo']['uid'],  //增加用户ID
           'ip'=>'',//$params['userinfo']['ip'],  //增加用户ID
           'pay_channel_id'=>$payChannelId,
           'return_url'=>$returnUrl,           //【必须】string 前端回跳地址
           'extended_params'=>$extendedParams,           //【必须】string 前端回跳地址
           'auth_token'=>$params['auth_token'],
       ];
       $res = ActivityDestineOperate::create($data);
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
     *		'activity_id'	=> '',	    //【必选】int 活动ID
     * ]
     * $request['userinfo']     //【必须】array 用户信息  - 转发接口获取
     * $userinfo [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'uid'=>1,   //【必须】string 用户ID
     *      'username'=>1, //【必须】string 用户名
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function destineQuery(Request $request){
        $params = $request->all();

        //获取appid
        $appid	   = $params['appid'];

        $userInfo   = isset($params['userinfo'])?$params['userinfo']:[];
        $userType   = isset($params['userinfo']['type'])?$params['userinfo']['type']:0;

        $activityId  = isset($params['params']['activity_id'])?$params['params']['activity_id']:0;

        //判断参数是否设置
        if(empty($appid) && $appid <1){
            return apiResponse([],ApiStatus::CODE_20001,"appid错误");
        }
        if($userType!=2 && empty($userInfo)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
        }

        if($activityId <1){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[活动ID错误]");
        }

        $data =[
            'activity_id'=>$activityId,
            'user_id'=>$params['userinfo']['uid'],  //增加用户ID
        ];
        $res = ActivityDestineOperate::destineQuery($data);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_51001,get_msg());
        }

        return apiResponse($res,ApiStatus::CODE_0);

    }

}