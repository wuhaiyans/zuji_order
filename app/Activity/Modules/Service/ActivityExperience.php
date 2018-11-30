<?php
/**
 * 1元活动
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Modules\Service;
use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\Activity\ActivityDestine;
use App\Activity\Modules\Repository\ActivityExperienceRepository;
use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use App\Order\Modules\Service\OrderNotice;
use Illuminate\Support\Facades\DB;
use App\Activity\Modules\Repository\Activity\ExperienceDestine;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderReturnRepository;
use Illuminate\Http\Request;

class ActivityExperience
{
    /**
     * 获取体验活动列表
     */
    public static function experienceList(){
       $experienceList1 = ActivityExperienceRepository::getActivityExperienceInfo();//获取体验活动信息
       if(!$experienceList1){
           return false;
       }
        $experienceList=objectToArray($experienceList1);
       foreach($experienceList as $key=>$item){
           $experienceList[$key]['group_type_name'] = DestineStatus::getActivityTypeName($item['group_type']);   //活动分组名称
           $experienceList[$key]['experience_status_name'] = DestineStatus::getExperienceStatusName($item['experience_status']);  //体验状态名称
           $experienceList[$key]['activity_name'] = DestineStatus::getExperienceActivityStatusName($item['activity_id']);   //活动类型
           $experienceList[$key]['zuqi_name'] = DestineStatus::getZuqiTypeStatusTypeName($item['zuqi']);   //租期类型
           $new_arr[$item['group_type']][] =  $experienceList[$key];
       }
       return $new_arr;

    }
    /**
     * 预定金退款----15个自然日内
     * @param
     * [
     *     'id'            =>  '' ,//预订编号   int     【必传】
     *     'refund_remark' => '', //退款备注  string   【必传】
     * ]
     */
    public function experienceRefund(array $params){
        //开启事务
        DB::beginTransaction();
        try{
            //获取预定信息
            $experienceDestineInfo = ExperienceDestine::getById($params['id']);
            if(!$experienceDestineInfo){
                LogApi::debug("[experienceRefund]获取预定信息失败");
                set_msg("获取预定信息失败");
                return false;
            }
            $experienceInfo=$experienceDestineInfo->getData();
            //如果预定状态为  已支付时可以退款
            if( $experienceInfo['destine_status'] == DestineStatus::DestinePayed){
                //支付方式是支付宝
                if($experienceInfo['pay_type'] == PayInc::FlowerStagePay){
                    //判断预定时间是否在15个自然日内
                    if(time() -$experienceInfo['pay_time'] > 15*24*3600)
                    {
                        LogApi::debug("[experienceRefund]预定时间必须在15个自然日内,预定创建时间".$experienceInfo['create_time']);
                        set_msg("预定时间必须在15个工作日内");
                        return false;
                    }
                }
                //获取支付信息
                $pay_result =  OrderReturnRepository::getPayNo(OrderStatus::BUSINESS_EXPERIENCE,$experienceInfo['destine_no']);
                if(!$pay_result){
                    LogApi::debug("[experienceRefund]获取订单的支付信息失败",$pay_result);
                    set_msg("获取订单的支付信息失败");
                    return false;
                }
                //创建清算
                $create_data['business_type']     = OrderStatus::BUSINESS_EXPERIENCE;//业务类型
                $create_data['business_no']       = $experienceInfo['destine_no'];//预定编号
                $create_data['out_payment_no']    = $pay_result['payment_no'];//支付编号
                $create_data['refund_amount']     = $experienceInfo['destine_amount'];//退款金额
                $create_data['auth_deduction_status'] = OrderCleaningStatus::depositDeductionStatusNoPay;//扣除押金状态
                $create_data['auth_unfreeze_status']  = OrderCleaningStatus::depositUnfreezeStatusNoPay;//退还押金状态
                $create_data['refund_status']     = OrderCleaningStatus::refundUnpayed;//退款状态
                $create_data['status']             = OrderCleaningStatus::orderCleaningUnRefund;//状态
                $create_data['pay_type']           = $experienceInfo['pay_type'];//支付类型
                $create_data['app_id']             = $experienceInfo['app_id'];//应用渠道
                $create_data['channel_id']        = $experienceInfo['channel_id'];//渠道id
                $create_data['user_id']            = $experienceInfo['user_id'];//用户id
                LogApi::debug("[experienceRefund]创建清单参数",$create_data);
                $create_clear=\App\Order\Modules\Repository\OrderClearingRepository::createOrderClean($create_data);//创建退款清单
                if(!$create_clear){
                    LogApi::debug("[appointmentRefund]创建退款清单失败",$pay_result);
                    set_msg("获取订单的支付信息失败");
                    //事务回滚
                    DB::rollBack();
                    return false;//创建退款清单失败
                }
                //更新预订状态为退款中
                $updateDestineRefund = $experienceDestineInfo->updateDestineRefund($params['refund_remark']);
                if(!$updateDestineRefund){
                    LogApi::debug("[appointmentRefund]更新预订状态为退款中失败",$pay_result);
                    set_msg("获取订单的支付信息失败");
                    //事务回滚
                    DB::rollBack();
                    return false;//更新失败
                }
                DB::commit();
                //订金退款申请成功发送短信
                   $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_EXPERIENCE, $experienceInfo['destine_no'] ,SceneConfig::DESTINE_CREATE);
                   $b=$orderNoticeObj->notify();
                     LogApi::debug($b?"destine_no :".$experienceInfo['destine_no']." IS OK":"IS error");
                return true;

            }else{
                //事务回滚
                DB::rollBack();
                LogApi::debug("[appointmentRefund]预定状态必须是已支付，已下单,预定状态值".$params['destine_no']);
                //不允许退预定金
                return false;
            }
        }catch( \Exception $exc){
            //事务回滚
            DB::rollBack();
            LogApi::error('[appointmentRefund]预定单退款异常',$exc);
            return false;
        }


    }
    /***
     * 预定金15个自然日之后的退款
     * @param array $params
     * [
     *    'id'            => ''   //预定id  int  【必传】
     *    'account_time'  =>''    //转账时间 int  【必传】
     *    'account_number'=>''   //支付宝账号string【必传】
     *    'refund_remark' =>''   //退款备注  string 【必传】
     * ]
     * @return bool
     */
    public function refund(array $params){

        //开启事务
        DB::beginTransaction();
        try {
            //获取预定信息
            $experienceDestineInfo = ExperienceDestine::getById($params['id']);
            if(!$experienceDestineInfo){
                LogApi::debug("[refund]获取预定信息失败");
                set_msg("获取预定信息失败");
                return false;
            }
            $experienceInfo=$experienceDestineInfo->getData();
            //获取支付信息
            $pay_result =  OrderReturnRepository::getPayNo(OrderStatus::BUSINESS_EXPERIENCE,$experienceInfo['destine_no']);
            if(!$pay_result){
                LogApi::debug("[refund]获取订单的支付信息失败",$pay_result);
                set_msg("获取订单的支付信息失败");
                return false;
            }
            //如果预定状态为  已支付，已下单时可以退款
            if ( $experienceInfo['destine_status'] == DestineStatus::DestinePayed) {
                //判断预定时间是否在15个自然日外
                if (time() - $experienceInfo['pay_time'] < 15 * 24 * 3600) {
                    LogApi::debug("[refund]预定时间必须在15个自然日外,预定创建时间" . $experienceInfo['create_time']);
                    set_msg("预定时间必须在15个工作日外");
                    return false;
                }
            }
            //修改预定的信息
            $updateActivityDestine = $experienceDestineInfo->updateActivityDestine($params);
            if (!$updateActivityDestine) {
                //事务回滚
                DB::rollBack();
                return false;
            }
            //事务提交
            DB::commit();
            //订金退款申请成功发送短信
            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_EXPERIENCE, $experienceInfo['destine_no'] ,SceneConfig::DESTINE_REFUND);
            $b=$orderNoticeObj->notify();
            LogApi::debug($b?"destine_no :".$experienceInfo['destine_no']." IS OK":"IS error");
            return true;
        }catch( \Exception $exc){
            //事务回滚
            DB::rollBack();
            LogApi::error('[refund]预定单退款异常',$exc);
            return false;
        }

    }
    /***
     * 退款成功回调
     * @param array $params 业务参数
     * [
     *		'business_type'=> '',//业务类型   int     【必传】
     *		'business_no' => '',//业务编码    string  【必传】
     *		'status'      => '',//支付状态    string  success：支付完成
     * ]
     * @param array $userinfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string    【必传】
     *      'type'     =>''   渠道类型     int       【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * @return bool
     */
    public static  function callbackExperience(array $params,array $userinfo){
        //参数过滤
        $rules = [
            'business_type'   => 'required',//业务类型
            'business_no'     => 'required',//业务编码
            'status'          => 'required',//支付状态
        ];
        $validator = app('validator')->make($params, $rules);
        if ($validator->fails()) {
            LogApi::debug("参数错误",$params);
            return false;
        }
        //必须是预定业务
        if($params['business_type'] != OrderStatus::BUSINESS_EXPERIENCE){
            return false;
        }
        try{
            //获取预定信息
            $experienceDestineInfo = ExperienceDestine::getByNo($params['business_no']);
            if(!$experienceDestineInfo){
                LogApi::debug("[callbackExperience]获取预定信息失败");
                set_msg("获取预定信息失败");
                return false;
            }
            if($params['status'] == "success"){
                $updateRefund=$experienceDestineInfo->refund();//更新预订单状态为  已退款
                if(!$updateRefund){
                    return false;
                }
                $destineInfo=$experienceDestineInfo->getData();
                //订金退款申请成功发送短信
                $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_DESTINE, $destineInfo['destine_no'] ,SceneConfig::DESTINE_REFUND);
                $b=$orderNoticeObj->notify();
                LogApi::debug($b?"destine_no :".$destineInfo['destine_no']." IS OK":"IS error");
                return true;
            }
        }catch (\Exception $exc) {
            LogApi::debug("程序异常",$exc);
            return false;

        }


    }


}