<?php
/**
 *  活动预定操作类
 *  author: wuhaiyan
 */
namespace App\Activity\Modules\Service;

use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\Activity\ActivityAppointment;
use App\Activity\Modules\Repository\Activity\ActivityDestine;
use App\Activity\Modules\Repository\ActivityDestineRepository;
use App\Lib\Channel\Channel;
use App\Lib\Common\LogApi;
use App\Lib\Order\OrderInfo;
use App\Order\Models\OrderPayModel;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\Pay\Pay;
use App\Order\Modules\Repository\Pay\PaymentStatus;
use App\Order\Modules\Repository\Pay\PayStatus;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class ActivityDestineOperate
{

    /**
     * 增加活动预定
     * @author wuhaiyan
     * @param $data[
     *
     *      'appid'=>'',                //【必须】 int appid
     *      'pay_type'=>'',             //【必须】 int 支付方式
     *      'activity_id'=>'',          //【必须】 int 活动ID
     *      'mobile'=>'',               //【必须】 string 用户手机号
     *      'user_id'=>'',              //【必须】 int 用户ID
     *      'ip'=>'',                   //【必须】 int 客户端IP地址
     *      'pay_channel_id'=>'',       //【必须】 int 支付渠道
     *      'return_url'=>'',           //【必须】string 前端回跳地址
     *      'auth_token'=>'',           //【小程序支付必选】auth_token
     *      'extended_params'=>[        //【小程序支付必选】array 扩展参数
     *          "alipay_params"=>[      //支付宝扩展参数
     *                  "trade_type"=>"APP"
     *          ],
     *          "wechat_params"=>[      //微信扩展参数
     *                  "openid"=>"oBjc20uu9n0R_uv2yAzRA0YHSVIs",
     *                  "trade_type"=>"JSAPI"
     *          ]
     *
     * ],
     * ]
     * @return bool
     */

    public static function create($data)
    {

        try {
            DB::beginTransaction();
            //判断用户是否 已经参与活动
            $res = ActivityDestineRepository::unActivityDestineByUser($data['user_id'],$data['activity_id']);
            //获取活动信息
            $activity = ActivityAppointment::getByIdInfo($data['activity_id']);
            if(!$activity){
                DB::rollBack();
                set_msg("获取活动信息失败");
                return false;
            }
            $activityInfo =$activity->getData();
            $activityName  =$activityInfo['title'];
            $destineAmount =$activityInfo['appointment_price'];
            //如果有预订记录
            if($res){
                $destine = objectToArray($res);
                //判断如果存在预定记录 更新预定时间
                if ($destine['destine_status'] == DestineStatus::DestineCreated) {
                    //根据appid 获取所在渠道
                    $ChannelInfo = Channel::getChannel($data['appid']);
                    if (!is_array($ChannelInfo)) {
                        DB::rollBack();
                        set_msg("获取渠道接口数据失败");
                        return false;
                    }
                    $channelId = intval($ChannelInfo['_channel']['id']);


                    $destineData = [
                        'activity_id' => $data['activity_id'],    //【必须】 int   活动ID
                        'user_id' => $data['user_id'],        //【必须】 int   用户ID
                        'mobile' => $data['mobile'],         //【必须】 string 用户手机号
                        'destine_amount' => $destineAmount,                     //【必须】 float  预定金额
                        'pay_type' => $data['pay_type'],       //【必须】 int  支付类型
                        'app_id' => $data['appid'],          //【必须】 int app_id
                        'channel_id' => $channelId,                     //【必须】 int 渠道Id
                        'activity_name' => $activityName,                     //【必须】 string 活动名称
                    ];

                    $activityDestine = ActivityDestine::getByNo($destine['destine_no']);
                    $b = $activityDestine->upDate($destineData);
                    if (!$b) {
                        LogApi::error("ActivitDestine-upERRO",$destineData);
                        DB::rollBack();
                        set_msg("更新预定时间错误");
                        return false;
                    }
                    $destine = $activityDestine->getData();
                } else {
                    DB::rollBack();
                    set_msg("活动已预订");
                    return false;
                }

            }
            //如果没有预订记录 则新增记录
            else{
                $destineNo = createNo("YD");  //生成预订编号


                //根据appid 获取所在渠道
                $ChannelInfo = Channel::getChannel($data['appid']);
                if (!is_array($ChannelInfo)) {
                    DB::rollBack();
                    set_msg("获取渠道接口数据失败");
                    return false;
                }
                $channelId = intval($ChannelInfo['_channel']['id']);


                $destine = [
                    'destine_no' => $destineNo,              //【必须】 string 预定编号
                    'activity_id' => $data['activity_id'],    //【必须】 int   活动ID
                    'user_id' => $data['user_id'],        //【必须】 int   用户ID
                    'mobile' => $data['mobile'],         //【必须】 string 用户手机号
                    'destine_amount' => $destineAmount,                     //【必须】 float  预定金额
                    'pay_type' => $data['pay_type'],       //【必须】 int  支付类型
                    'app_id' => $data['appid'],          //【必须】 int app_id
                    'channel_id' => $channelId,                     //【必须】 int 渠道Id
                    'activity_name' => $activityName,                     //【必须】 string 活动名称
                ];

                $activityDestine = new ActivityDestineRepository();
                $b = $activityDestine->add($destine);
                if (!$b) {
                    LogApi::error("ActivitDestine-addERRO",$destine);
                    DB::rollBack();
                    set_msg("活动添加失败");
                    return false;
                }
            }
            if($destine['destine_amount'] <=0){
                DB::rollBack();
                set_msg("报名金额不能为0".json_encode($destine));
                return false;
                $destine['destine_amount'] =0.01;
            }
            // 微信支付，交易类型：JSAPI，redis读取openid
            if( $data['pay_channel_id'] == \App\Order\Modules\Repository\Pay\Channel::Wechat ){
                if( isset($data['extended_params']['wechat_params']['trade_type']) && $data['extended_params']['wechat_params']['trade_type']=='JSAPI' ){
                    $_key = 'wechat_openid_'.$data['auth_token'];
                    $openid = \Illuminate\Support\Facades\Redis::get($_key);
                    if( $openid ){
                        $data['extended_params']['wechat_params']['openid'] = $openid;
                    }
                }
            }

            //生成支付单
                $businessNo =$destine['destine_no'];
                $params = [
                    'userId'            => $data['user_id'],//用户ID
                    'businessType'		=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_DESTINE,	// 业务类型
                    'businessNo'		=> $businessNo,	                // 业务编号
                    'orderNo'		    => '',	// 订单号
                    'paymentAmount'		=> $destine['destine_amount'],	                    // Price 支付金额，单位：元
                    'paymentFenqi'		=> '0',	// int 分期数，取值范围[0,3,6,12]，0：不分期
                ];



                $payModel = new OrderPayModel();


                //查询支付单是否存在
                $res = $payModel->where('business_no','=',$params['businessNo'])->count();
                if(!$res){
                    $payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($params);
                }else{
                    $info = $payModel->where('business_no','=',$params['businessNo'])->first()->toArray();
                    $params['status'] = PayStatus::WAIT_PAYMENT;
                    $params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;
                    $_data = [
                        'user_id'		=> $params['userId'],
                        'order_no'		=> $params['orderNo'],
                        'business_type'	=> $params['businessType'],
                        'business_no'	=> $params['businessNo'],
                        'status'		=> $params['status'],
                        'create_time'	=> time(),

                        'payment_status'	=> $params['paymentStatus'],
                        'payment_no'		=> $info['payment_no'],
                        'payment_amount'	=> $params['paymentAmount'],
                        'payment_fenqi'		=> $params['paymentFenqi'],

                    ];
                    $payResult = new Pay($_data);
                }
            //获取支付的url
            $url = $payResult->getCurrentUrl($data['pay_channel_id'], [
                'name'=>$destine['activity_name'].'活动的预定金额：'.$destine['destine_amount'],
                'front_url' => $data['return_url'], //回调URL
                'ip'=>$data['ip'],
                'extended_params'=>$data['extended_params'],
            ]);
            // 提交事务
            DB::commit();
            return $url;

        } catch (\App\Lib\ApiException $ex) {
            DB::rollBack();
            set_msg("网络异常");
            return false;
        } catch (\Exception $exc) {
            DB::rollBack();
            set_msg($exc->getMessage());
            return false;
        }
    }

    /**
     * 活动预定 查询接口
     * @author wuhaiyan
     * @param $data[
     *
     *      'activity_id'=>'',          //【必须】 int 活动ID
     *      'user_id'=>'',              //【必须】 int 用户ID
     * ]
     * @return bool
     */

    public static function destineQuery($data)
    {
            $res =[];
            //判断用户是否 已经参与活动
            $destine = ActivityDestineRepository::unActivityDestineByUser($data['user_id'],$data['activity_id']);

            //获取活动信息
            $activity = ActivityAppointment::getByIdInfo($data['activity_id']);
            if(!$activity){
                set_msg("获取活动信息失败");
                return false;
            }
            $activityInfo =$activity->getData();
            $res['destine_amount'] = $activityInfo['appointment_price'];
            $res['activity_status'] = 0;
            if(time()>=$activityInfo['end_time']){
                $res['activity_status'] = 1;
            }
            //如果有预订记录
            if($destine){
                $destine = objectToArray($destine);
                //判断如果存在预定记录 更新预定时间
                if ($destine['destine_status'] != DestineStatus::DestineCreated) {
                    $res['status'] =1;
                }else{
                    $res['status'] =0;
                }
            }else{
                $res['status'] =0;

            }
            return $res;

    }

    /***
     * 获取预定单列表
     * @param array $param
     * @param int $pagesize
     */
    public static function getDestineExportList($param = array()){
        //根据条件查找预定单列表

        $destineListArray = ActivityDestineRepository::getDestineList($param);
        if (empty($destineListArray)) return false;

        if (!empty($destineListArray)) {

            foreach ($destineListArray as $keys=>$values) {

                //定金状态名称
                $destineListArray[$keys]['destine_status_name'] = DestineStatus::getStatusName($values['destine_status']);
                //支付方式名称
                $destineListArray[$keys]['pay_type_name'] = PayInc::getPayName($values['pay_type']);
                //应用来源名称
                $destineListArray[$keys]['appid_name'] = OrderInfo::getAppidInfo($values['app_id'],$values['channel_id']);

            }

        }

        return $destineListArray;

    }
    /***
     * 获取预定单列表
     * @param array $param
     * @param int $pagesize
     */
    public static function getDestineList($param = array()){
        //根据条件查找预定单列表

        $destineListArray = ActivityDestineRepository::getDestinePageList($param);
        if (empty($destineListArray)) return false;

        if (!empty($destineListArray)) {

            foreach ($destineListArray['data'] as $keys=>$values) {
                //定金状态名称
                $destineListArray['data'][$keys]->destine_status_name = DestineStatus::getStatusName($destineListArray['data'][$keys]->destine_status);
                //支付方式名称
                $destineListArray['data'][$keys]->pay_type_name = PayInc::getPayName($destineListArray['data'][$keys]->pay_type);
                //应用来源名称
                $destineListArray['data'][$keys]->appid_name = OrderInfo::getAppidInfo($destineListArray['data'][$keys]->app_id);
                if( $destineListArray['data'][$keys]->destine_status == DestineStatus::DestinePayed ){
                    if($destineListArray['data'][$keys]->pay_type ==PayInc::WeChatPay){
                        $destineListArray['data'][$keys]->refundOperateBefore = true;
                        $destineListArray['data'][$keys]->refundOperateAfter = false;
                    }else{
                        //15个自然日之内
                        if($destineListArray['data'][$keys]->pay_time + 15*24*3600 >time()){
                            $destineListArray['data'][$keys]->refundOperateBefore = true;
                        }else{
                            $destineListArray['data'][$keys]->refundOperateBefore = false;

                        }
                        //15个自然日后
                        if($destineListArray['data'][$keys]->pay_time + 15*24*3600<time()){
                            $destineListArray['data'][$keys]->refundOperateAfter = true;
                        }else{
                            $destineListArray['data'][$keys]->refundOperateAfter = false;
                        }
                    }


                    $destineListArray['data'][$keys]->selectOperate = false;
                }else if($destineListArray['data'][$keys]->destine_status ==DestineStatus::DestineRefunded){
                    $destineListArray['data'][$keys]->selectOperate = true;
                    $destineListArray['data'][$keys]->refundOperateAfter = false;
                    $destineListArray['data'][$keys]->refundOperateBefore = false;

                }else{
                    $destineListArray['data'][$keys]->refundOperateAfter = false;
                    $destineListArray['data'][$keys]->refundOperateBefore = false;
                    $destineListArray['data'][$keys]->selectOperate = false;
                }


            }

        }

        return $destineListArray;

    }
    /***
     * 获取预定单列表【获取详情信息】
     * @param array $param
     * @param int $destine_no
     * @return array
     */
    public static function getDestineLogList($destine_no){
         $activityInfo=ActivityDestine::getByNo($destine_no);
         if(!$activityInfo){
             return false;
         }
        $activityDestineInfo = $activityInfo->getData();

         return $activityDestineInfo;

    }




}