<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Activity\Controllers\Api\v1;



use App\Activity\Modules\Repository\ActivityDestineRepository;
use App\Activity\Modules\Service\ActivityDestineOperate;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
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

       $userName =isset($params['userinfo']['username'])?$params['userinfo']['username']:'';
       $userId =isset($params['userinfo']['uid'])?$params['userinfo']['uid']:0;
       $userIp =isset($params['userinfo']['ip'])?$params['userinfo']['ip']:'';

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
           'mobile'=>$userName,
           'user_id'=>$userId,  //增加用户ID
           'ip'=>$userIp,  //增加用户ID
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
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function destineExport(Request $request){

        set_time_limit(0);
        try{
            $params = $request->all();
            $params['page']     = !empty($params['page']) ? $params['page'] : 1;
            $outPages           = !empty($params['page']) ? $params['page'] : 1;

            $total_export_count = 5000;
            $pre_count = 500;
            $smallPage = ceil($total_export_count/$pre_count);
            $i = 1;
            LogApi::debug("预约导出接收参数",$params);
            header ( "Content-type:application/vnd.ms-excel" );
            header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "后台分期列表数据导出" ) . ".csv" );

            // 打开PHP文件句柄，php://output 表示直接输出到浏览器
            $fp = fopen('php://output', 'a');

            // 表头
            $headers = ['预订编号','预订时间','用户手机', '所属渠道','支付方式','交易流水号','订金状态'];

            // 将中文标题转换编码，否则乱码
            foreach ($headers as $k => $v) {
                $column_name[$k] = iconv('utf-8', 'GB18030', $v);
            }

            // 将标题名称通过fputcsv写到文件句柄
            fputcsv($fp, $column_name);

            while(true){
                if ($i > $smallPage) {
                    exit;
                }
                $offset = ( $outPages - 1) * $total_export_count;
                $params['page'] = intval(($offset / $pre_count) + $i) ;
                LogApi::debug("预约导出页数的参数值".$params['page']);
                ++$i;
                $destineData = array();
                $destineData = ActivityDestineOperate::getDestineExportList($params);
                LogApi::debug("预约导出查询后导出的结果是",$destineData);
                if ($destineData) {
                    $data = array();
                    foreach ($destineData as $item) {
                        $data[] = [
                            $item['destine_no'],
                            $item['pay_time'],
                            $item['mobile'],
                            $item['appid_name'],
                            $item['pay_type_name'],
                            $item['account_number'],
                            $item['destine_status_name'],
                        ];
                    }

                }else{
                    break;
                }

                $Excel =  Excel::csvOppointmentListWrite($data, $fp);
            }

            return $Excel;

        } catch (\Exception $e) {

            return apiResponse([],ApiStatus::CODE_95002,$e);

        }



    }
    /***
     * 预定单列表
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function destineList(Request $request)
    {
        try{
            $params = $request->all();
            $destineData = ActivityDestineOperate::getDestineList($params['params']);
            if(!$destineData){
                return apiResponse([],ApiStatus::CODE_50001);  //获取预订信息失败
            }

            return apiResponse($destineData,ApiStatus::CODE_0);
        }catch (\Exception $e) {
            LogApi::error('预订单列表异常',$e);
            return apiResponse([],ApiStatus::CODE_50000);

        }

    }

    /**
     * 获取详情日志
     * @param Request $request
     */
    public function destineDetailLog(Request $request){
        $params = $request->all();
        if(empty($params['params']['destine_no'])){
            return apiResponse([],ApiStatus::CODE_20001);  //参数不能为空
        }
        $destineData = ActivityDestineOperate::getDestineLogList($params['params']['destine_no']);
        if(!$destineData){
            return apiResponse([],ApiStatus::CODE_50001);  //获取预订信息失败
        }

        return apiResponse($destineData,ApiStatus::CODE_0);

    }



}