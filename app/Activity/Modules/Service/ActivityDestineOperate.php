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
use App\Common\LogApi;
use App\Lib\Channel\Channel;
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
     *      'pay_channel_id'=>'',       //【必须】 int 支付渠道
     *      'ip'=>'',                   //【必须】string ip地址
     *      'return_url'=>'',           //【必须】string 前端回跳地址
     * ]
     * @return bool
     */

    public static function create($data)
    {
        try {
            DB::beginTransaction();
            //判断用户是否 已经参与活动
            $destine = ActivityDestineRepository::unActivityDestineByUser($data['user_id'],$data['activity_id']);

            //如果有预订记录
            if($destine){

                $destine = objectToArray($destine);
                //判断如果存在预定记录 更新预定时间
                if ($destine['destine_status'] == DestineStatus::DestineCreated) {
                    $activityDestine = ActivityDestine::getByNo($destine['destine_no']);
                    $b = $activityDestine->upCreateTime();
                    if (!$b) {
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
                //获取活动信息
                $activity = ActivityAppointment::getByIdInfo(2);
                if(!$activity){
                    DB::rollBack();
                    set_msg("获取活动信息失败");
                    return false;
                }
                $activityInfo =$activity->getData();
                $activityName  =$activityInfo['title'];
                $destineAmount =$activityInfo['appointment_price'];

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
                    DB::rollBack();
                    set_msg("活动添加失败");
                    return false;
                }
            }
            //生成支付单
            $businessNo =$destine['destine_no'];
            $payData = [
                'userId'            => $data['user_id'],//用户ID
                'businessType'		=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_DESTINE,	// 业务类型
                'businessNo'		=> $businessNo,	                // 业务编号
                'orderNo'		    => '',	// 订单号
                'paymentAmount'		=> $destine['destine_amount'],	                    // Price 支付金额，单位：元
                'paymentFenqi'		=> '0',	// int 分期数，取值范围[0,3,6,12]，0：不分期
            ];
            $payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payData);
            //获取支付的url
            $url = $payResult->getCurrentUrl($data['pay_channel_id'], [
                'name'=>$destine['activity_name'].'活动的预定金额：'.$destine['destine_amount'],
                'front_url' => $data['return_url'], //回调URL
                'ip' => $data['ip'], // 客户端IP
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





}