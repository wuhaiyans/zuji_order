<?php
/**
 *  活动体验预定操作类
 *  author: wuhaiyan
 */
namespace App\Activity\Modules\Service;

use App\Activity\Modules\Inc\DestineInc;
use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\ActiveInviteRepository;
use App\Activity\Modules\Repository\Activity\ExperienceDestine;
use App\Activity\Modules\Repository\ActivityThemeRepository;
use App\Activity\Modules\Repository\ExperienceDestineRepository;
use App\Lib\Channel\Channel;
use App\Lib\Common\LogApi;
use App\Lib\Order\OrderInfo;
use App\Lib\User\User;
use App\Order\Modules\Inc\PayInc;
use Illuminate\Support\Facades\DB;
use App\Activity\Modules\Repository\Activity;

class ExperienceDestineOperate
{

    /**
     * 增加活动预定
     * @author wuhaiyan
     * @param $data[
     *
     *      'appid'=>'',                //【必须】 int appid
     *      'experience_id'=>'',        //【必须】 int 活动ID
     *      'mobile'=>'',               //【必须】 string 用户手机号
     *      'user_id'=>'',              //【必须】 int 用户ID
     *      'ip'=>'',                   //【必须】 int 客户端IP地址
     *      'pay_channel_id'=>'',       //【必须】 int 支付渠道
     *      'pay_type'	=> '',	        //【必选】 int 支付方式
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
            $destineNo = createNo("TY");  //生成体验预订编号
            //查询活动信息
            $activity = \App\Activity\Modules\Repository\Activity\ActivityExperience::getByIdNo($data['experience_id']);
            if(!$activity){
                DB::rollBack();
                set_msg("活动不存在");
                return false;
            }

            $activityInfo = $activity->getData();

            $activity = ActivityThemeRepository::getInfo(['activity_id'=>$activityInfo['activity_id']]);
            if(!$activity){
                DB::rollBack();
                set_msg("活动不存在");
                return false;
            }

            if(time()>=$activity['end_time']){
                DB::rollBack();
                set_msg("活动已结束");
                return false;
            }

            if($activityInfo['experience_status']== DestineStatus::BeAlreadyFull){
                DB::rollBack();
                set_msg("该活动已约满");
                return false;
            }


            //根据appid 获取所在渠道
            $ChannelInfo = Channel::getChannel($data['appid']);
            if (!is_array($ChannelInfo)) {
                DB::rollBack();
                set_msg("获取渠道接口数据失败");
                return false;
            }
            $channelId = intval($ChannelInfo['_channel']['id']);

            //根据user_id 获取 oppen_id
//            $openid="";
//            $userWechat = User::getUserWechat($data['user_id']);
//            if($userWechat){
//                $openid = $userWechat['openid'];
//            }
            $openid ='';
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
            
            //判断用户是否 已经参与活动
            $destine = ExperienceDestineRepository::unActivityDestineByUser($data['user_id'],$activityInfo['activity_id']);
            if($destine){

                $destine = objectToArray($destine);
                //判断如果存在预定记录 更新预定时间
                if($destine['destine_status'] != DestineStatus::DestineCreated){
                    DB::rollBack();
                    set_msg("活动已预订");
                    return false;
                }

                //如果记录存在 则更新记录  不做更新 要重新生成2018/12/24
//                $destineData = [
//                    'destine_no'    =>$destineNo,                       //【必须】 string 活动编号
//                    'mobile'        =>$data['mobile'],                  //【必须】 string 用户手机号
//                    'experience_id' =>$data['experience_id'],           //【必须】 int    体验活动ID
//                    'zuqi'          =>$activityInfo['zuqi'],            //【必须】 int    租期
//                    'destine_amount'=>$activityInfo['destine_amount'],  //【必须】 string 支付金额
//                    'pay_channel'   =>$data['pay_channel_id'],          //【必须】 int    支付渠道
//                    'app_id'        =>$data['appid'],                   //【必须】 int    appid
//                    'pay_type'      =>$data['pay_type'],                //【必须】 int    支付方式
//                    'channel_id'    =>$channelId,                       //【必须】 int    渠道ID
//                    'open_id'       =>$openid,                          //【必须】 int    oppen_id
//                    'goods_name'    =>$activityInfo['goods_name']           //【必须】 int    商品名称
//                ];
//
//                $activityDestine = ExperienceDestine::getByNo($destine['destine_no']);
//                $b = $activityDestine->upDate($destineData);
//                if (!$b) {
//                    DB::rollBack();
//                    LogApi::error("ActivitDestine-upERRO",$destineData);
//                    set_msg("更新预定记录错误");
//                    return false;
//                }

            }
            //新增记录
                $destineData =[
                    'destine_no'    => $destineNo,                      //【必须】 string 预定编号
                    'activity_id'   => $activityInfo['activity_id'],    //【必须】 int   总活动ID
                    'experience_id' => $activityInfo['id'],             //【必须】 int   活动ID
                    'user_id'       => $data['user_id'],                //【必须】 int   用户ID
                    'mobile'        => $data['mobile'],                 //【必须】 string 用户手机号
                    'destine_amount'=> $activityInfo['destine_amount'], //【必须】 float  预定金额
                    'pay_type'      => $data['pay_type'],               //【必须】 int  支付类型
                    'app_id'        => $data['appid'],                  //【必须】 int app_id
                    'channel_id'    => $channelId,                      //【必须】 int 渠道Id
                    'pay_channel'   => $data['pay_channel_id'],         //【必须】 string 支付渠道
                    'zuqi'          => $activityInfo['zuqi'],           //【必须】 int 租期
                    'open_id'       =>$openid,                          //【必须】 int    oppen_id
                    'goods_name'    =>$activityInfo['goods_name']           //【必须】 int    商品名称
                ];

                $activityDestine = new ExperienceDestineRepository();
                $b = $activityDestine->add($destineData);
                if (!$b) {
                    DB::rollBack();
                    LogApi::error("ActivitDestine-addERRO",$destineData);
                    set_msg("活动添加失败");
                    return false;
                }


            //生成支付单
            $params = [
                'userId'            => $data['user_id'],//用户ID
                'businessType'		=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_EXPERIENCE,	// 业务类型
                'businessNo'		=> $destineData['destine_no'],	                // 业务编号
                'orderNo'		    => '',	// 订单号
                'paymentAmount'		=> $destineData['destine_amount'],	                    // Price 支付金额，单位：元
                'paymentFenqi'		=> '0',	// int 分期数，取值范围[0,3,6,12]，0：不分期
            ];

            $payResult =\App\Order\Modules\Repository\Pay\PayCreater::createPayment($params);
            //获取支付的url
            $url = $payResult->getCurrentUrl($data['pay_channel_id'], [
                'name'=>$destine['destine_amount'].'元 体验活动预定',
                'front_url' => $data['return_url'], //回调URL
                'ip'=>$data['ip'],
                'extended_params'=>$data['extended_params'],
            ]);
            // 提交事务
            DB::commit();
            return $url;

        } catch (\App\Lib\ApiException $ex) {
            DB::rollBack();
            set_msg($ex->getOriginalValue());
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
     *      'experience_id'=>'',          //【必须】 int 活动体验ID
     *      'user_id'=>'',              //【必须】 int 用户ID
     * ]
     * @return bool
     */

    public static function experienceDestineQuery($data)
    {
            $res =[];
            //查询活动信息

            $activity = \App\Activity\Modules\Repository\Activity\ActivityExperience::getByActivityId($data['activity_id']);
            if(!$activity){
                set_msg("活动不存在");
                return false;
            }

            $activityInfo = $activity->getData();
            $res['invitation_code'] ='';
            $res['activity_status'] =0;

            $activity = ActivityThemeRepository::getInfo(['activity_id'=>$activityInfo['activity_id']]);
            if(!$activity){
                set_msg("活动不存在");
                return false;
            }

            if(time()>=$activity['end_time']){
                $res['activity_status'] =1;
            }

            //判断用户是否 已经参与活动
            $destine = ExperienceDestineRepository::unActivityDestineByUser($data['user_id'],$data['activity_id']);
            //如果有预订记录
            if($destine){
                $destine = objectToArray($destine);
                //判断如果不等于已创建状态 则为 已预订
                if ($destine['destine_status'] != DestineStatus::DestineCreated) {
                    $res['status'] =1;
                    $res['invitation_code'] =self::setInvitationCode([
                        'activity_id'   => $data['activity_id'],
                        'user_id'       => $destine['user_id'],
                    ]);
                }else{
                    $res['status'] =0;
                }
            }else{
                $res['status'] =0;

            }
            return $res;

    }

    /**
     * 设置 邀请码
     * @author wuhaiyan
     * @param $params
     * [
     *      'user_id'=>''   ,//【必须】int 用户ID
     *      'activity_id' =>'',//【必须】int 活动ID
     *]
     * @return string
     */


    public static function setInvitationCode($params){

        $data = filter_array($params, [
            'activity_id' => 'required',
            'user_id'       => 'required',
        ]);
        if(count($data)!=2){
            set_msg("参数错误");
            return false;
        }

        $str =substr(md5(DestineInc::DestineKey),0,5);
        $userStr =base64_encode($data['user_id']."*".$data['activity_id']);

        return $str.$userStr;

    }
    /**
     * 通过邀请码 获取邀请信息
     * @author wuhaiyan
     * @param      $invitationCode【必须】string 邀请编码
     * @return array
     * [
     *      'user_id'=>''   ,//【必须】int 用户ID
     *      'experience_id' =>'',//【必须】int 活动ID
     * ]
     */


    public static function getInvitationCode($invitationCode){

        $ret =[];
        if(!isset($invitationCode)){
            set_msg("参数错误");
            return false;
        }
         $str =substr(md5(DestineInc::DestineKey),0,5);
         $key =substr($invitationCode,0,5);

         if($key != $str){
             set_msg("邀请码错误");
             return false;
         }
        $userstr =base64_decode(substr($invitationCode,5));
        $arr =explode("*",$userstr);
        $ret['user_id']=$arr[0];
        $ret['activity_id'] =$arr[1];

        return $ret;

    }
    /***
     * 获取预定活动列表
     * @param array $param
     * @param int $pagesize
     */
    public static function getDestineList($param = array()){
        //根据条件查找预定单列表

        $destineListArray = ExperienceDestineRepository::getDestinePageList($param);
        $destineListArray['data']=objectToArray($destineListArray['data']);
        foreach ($destineListArray['data'] as $k=>$v){
            $invite_count = ActiveInviteRepository::getCount([
                'uid'=>$v['user_id'],
                'activity_id' =>$v['activity_id']
            ]);
            $destineListArray['data'][$k]['invite_count'] = $invite_count;

            //定金状态名称
            $destineListArray['data'][$k]['destine_status_name'] = DestineStatus::getStatusName($v['destine_status']);
            //支付方式名称
            $destineListArray['data'][$k]['pay_type_name'] = PayInc::getPayName($v['pay_type']);
            //应用来源名称
            $destineListArray['data'][$k]['app_id_name'] = OrderInfo::getAppidInfo($v['app_id']);
            if( $destineListArray['data'][$k]['destine_status'] == DestineStatus::DestinePayed ){
                if($destineListArray['data'][$k]['pay_type'] ==PayInc::WeChatPay){
                    $destineListArray['data'][$k]['refundOperateBefore'] = true;
                    $destineListArray['data'][$k]['refundOperateAfter'] = false;
                }else{
                    //15个自然日之内
                    if($destineListArray['data'][$k]['pay_time'] + 15*24*3600 >time()){
                        $destineListArray['data'][$k]['refundOperateBefore'] = true;
                    }else{
                        $destineListArray['data'][$k]['refundOperateBefore'] = false;

                    }
                    //15个自然日后
                    if($destineListArray['data'][$k]['pay_time'] + 15*24*3600<time()){
                        $destineListArray['data'][$k]['refundOperateAfter'] = true;
                    }else{
                        $destineListArray['data'][$k]['refundOperateAfter'] = false;
                    }
                }
            }else if($destineListArray['data'][$k]['destine_status'] ==DestineStatus::DestineRefunded){
                $destineListArray['data'][$k]['refundOperateAfter'] = false;
                $destineListArray['data'][$k]['refundOperateBefore'] = false;

            }else{
                $destineListArray['data'][$k]['refundOperateAfter'] = false;
                $destineListArray['data'][$k]['refundOperateBefore'] = false;
            }

        }
        return $destineListArray;

    }
    /***
     * 获取预定活动列表
     * @param array $param
     * [
     *   'activity_id' => '',  // 【必选】 活动ID
     *   'user_id'    =>'' , //【必选】 用户ID
     *   'page'=>'' ,  //【可选】 string 页数
     *   'size'  =>'' ,  //【可选】 string 每页数量
     * ]
     * @param int $pagesize
     */
    public static function getDestineDetail($param = array()){

        $destineListArray = ActiveInviteRepository::getDestinePageList($param);
        return $destineListArray;
    }



}