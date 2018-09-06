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
use App\Lib\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


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
       if(empty($appid) && $appid <1){
           return apiResponse([],ApiStatus::CODE_20001,"appid错误");
       }
       if($userType!=2 && empty($userInfo)){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
       }
       if($payType <1){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付方式错误]");
       }
       if($payChannelId <1){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[支付渠道]");
       }
       if($activityId <1){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[活动ID错误]");
       }
      if(!isset($returnUrl)){
           return apiResponse([],ApiStatus::CODE_20001,"参数错误[return_url 未设置错误]");
       }

       $data =[
           'appid'=>$appid,
           'pay_type'=>$payType,
           'activity_id'=>$activityId,
           'mobile'=>$params['userinfo']['username'],
           'user_id'=>$params['userinfo']['uid'],  //增加用户ID
           'ip'=>$params['userinfo']['ip'],  //增加用户ID
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
    /***
     * 预定单导出
     * @param Request $request
     */
    public function destineExport(Request $request){
        $params = $request->all();
        // 表头
        $headers = ['预定编号','活动id','活动名称', '用户手机号','用户id','定金状态','预定金额','支付方式','渠道id','创建时间', '支付时间',
            '更新时间','商品父id','子商品id'.'转账时间','支付宝账号','转账备注'];

        $orderExcel = array();
        while(true) {

            $destineData = array();
            $destineData = ActivityDestineOperate::getDestineExportList($params);
            if ($destineData) {
                $data = array();
                foreach ($destineData as $item) {
                    $data[] = [
                        $item['destine_no'],
                        $item['activity_id'],
                        $item['activity_name'],
                        $item['mobile'],
                        $item['user_id'],
                        $item['destine_status_name'],
                        $item['destine_amount'],
                        $item['pay_type_name'],
                        $item['appid_name'],
                        $item['create_time'],
                        $item['pay_time'],
                        $item['update_time'],
                        $item['spu_id'],
                        $item['sku_id'],
                        $item['account_time'],
                        $item['account_number'],
                        $item['refund_remark'],
                    ];
                }

                $orderExcel =  Excel::csvWrite1($data,  $headers, '预订单列表',$abc);

            } else {
                break;
            }
        }

        return $orderExcel;

    }

}