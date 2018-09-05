<?php
/**
 * 预约活动
 * @access public (访问修饰符)
 * @author qinliping <qinliping@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Activity\Modules\Service;

use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\Activity\ActivityAppointment;
use App\Activity\Modules\Repository\Activity\ActivityDestine;
use App\Lib\Common\LogApi;
use App\Lib\Payment\CommonRefundApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Activity\Modules\Repository\ActivityAppointmentRepository;
use App\Activity\Modules\Repository\ActivityGoodsAppointmentRepository;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\Pay\PayQuery;
use Illuminate\Support\Facades\DB;

class Appointment
{
    /**
     * 添加预约活动
     * @param $params
     * [
     * 'title'             =>'',  标题           string    【必传】
     * 'appointment_price' =>'',  预定金额       string 【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int    【必传】
     * 'appointment_status' =>'', 活动状态       int    【必传】
     * 'spu_id'             =>['',''] 商品id     int    【必传】
     * ]
     * return bool
     */
    public function appointmentAdd(array $params)
    {
        DB::beginTransaction();
        try {
            $data['title'] = $params['title'];               //活动标题
            $data['appointment_price'] = $params['appointment_price']; //预定金额
            $data['appointment_image'] = $params['appointment_image']; //活动图片
            $data['desc'] = $params['desc'];                //活动描述
            $data['begin_time'] = $params['begin_time'];         //活动开始时间
            $data['end_time'] = $params['end_time'];            //活动结束时间
            $data['appointment_status'] = $params['appointment_status'];//活动状态
            $data['create_time'] = time();                          //创建时间
            $appointment_id = ActivityAppointmentRepository::add($data);//添加预约活动信息，获取添加活动的id
            if (!$appointment_id) {
                DB::rollBack();
                return false;
            }
            //循环添加活动和商品的关联关系
            foreach ($params['spu_id'] as $spu_id) {
                $goodsParams['appointment_id'] = $appointment_id;  //活动id
                $goodsParams['spu_id'] = $spu_id;                   //商品id
                $goodsParams['create_time'] = time();              //创建时间
                $res = ActivityGoodsAppointmentRepository::add($goodsParams);//执行添加
            }
            if(!$res){
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        }catch( \Exception $exc){
            DB::rollBack();
            LogApi::debug("程序异常",$exc);
            return false;
        }

    }


    /***
     * 执行修改活动
     * @param $data
     * [
     * 'id'                =>'',  活动id         int    【必传】
     * 'title'             =>'',  标题           string    【必传】
     * 'appointment_price' =>'',  预定金额       string 【必传】
     * 'appointment_image' =>'',  活动图片       string 【必传】
     * 'desc'              =>'',  活动描述       string 【必传】
     * 'begin_time'        =>'',  活动开始时间   int    【必传】
     * 'end_time'          =>''   活动结束时间   int    【必传】
     * 'appointment_status' =>'', 活动状态       int    【必传】
     * 'spu_id'             =>['',''] 商品id     int     【必传】
     * ]
     * @return bool
     */
    public function appointmentUpdate(array $params){
        DB::beginTransaction();
        try{
            //获取修改活动信息
            $activityInfo=ActivityAppointment::getByIdInfo($params['id']);
            if(!$activityInfo){
                return false;
            }
            //修改活动信息
            $activityUpdate= $activityInfo->activityUpdate($params);
            if(!$activityUpdate){
                LogApi::info("[appointmentUpdate]修改活动失败".$activityUpdate);
                DB::rollBack();
                return false;
            }
            //获取活动和商品的关联关系数据
            $activityGoodsInfo=ActivityGoodsAppointmentRepository::getByIdInfo($params['id']);
            if(!$activityGoodsInfo){
                return false;
            }
            $data['update_time']=time();
            foreach($activityGoodsInfo as $val){
                if(!in_array($val['spu_id'],$params['spu_id'])){
                    $data['goods_status'] = 1;
                    //禁用活动和商品的关联数据，重新添加活动和商品的关联关系
                    $closeActivityGoods=ActivityGoodsAppointmentRepository::closeActivityGoods($params['id'],$val['spu_id'],$data);
                    if(!$closeActivityGoods){
                        DB::rollBack();
                        return false;
                    }
                }else if(in_array($val['spu_id'],$params['spu_id']) && $val['goods_status'] == 1){
                    //活动商品启用
                    $data['goods_status'] = 0;
                    $closeActivityGoods=ActivityGoodsAppointmentRepository::closeActivityGoods($params['id'],$val['spu_id'],$data);
                    if(!$closeActivityGoods){
                        DB::rollBack();
                        return false;
                    }

                }
                $activityGoods[]=$val['spu_id'];

            }


            foreach($params['spu_id'] as $spu_id){
                if(!in_array($spu_id,$activityGoods)){
                    $goodsParams['appointment_id'] = $params['id'];
                    $goodsParams['spu_id'] = $spu_id;
                    $goodsParams['create_time'] = time();
                    $addActivityGoods = ActivityGoodsAppointmentRepository::add($goodsParams);//执行添加
                    if(!$addActivityGoods){
                        LogApi::info("[appointmentUpdate]循环添加活动和商品的关联关系失败".$addActivityGoods);
                        DB::rollBack();
                        return false;
                    }
                }
            }




             DB::commit();
             return true;
        }catch( \Exception $exc){
            DB::rollBack();
            LogApi::debug("程序异常",$exc);
            return false;
        }


    }

    /***
     * 获取活动信息
     * [
     *   'page'  =>'', //页数   int   【可选】
     *   'size'  =>'', //条数   int   【可选】
     * ]
     * @return array
     */
    public function appointmentList($params){
        $page = empty($params['page']) ? 1 : $params['page'];  //页数
        $size = !empty($params['size']) ? $params['size'] : config('web.pre_page_size'); //条数
        $activityInfo=ActivityAppointmentRepository::getActivityInfo($page,$size);
        if(!$activityInfo){
            return false;
        }
        foreach($activityInfo as $key=>$val){
            if($val['appointment_status'] == 0){
                $activityInfo[$key]['appointment_status'] ='开启';
            }else{
                $activityInfo[$key]['appointment_status'] ='禁用';
            }
        }

        return $activityInfo;

    }

    /**
     * 预定金退款----15个自然日内
     * @param int $id
     */
    public function appointmentRefund(int $id){
        //获取预定信息
        $activityDestineInfo=ActivityDestine::getByIdNo($id);
        if(!$activityDestineInfo){
            LogApi::debug("[appointmentRefund]获取预定信息失败");
            set_msg("获取预定信息失败");
            return false;
        }
        $destineInfo=$activityDestineInfo->getData();
        //如果预定状态为  已支付，已下单时可以退款
        if($destineInfo['destine_status'] == DestineStatus::DestineOrderCreated || $destineInfo['destine_status'] == DestineStatus::DestinePayed){
            //判断预定时间是否在15个自然日内
            if(time() -$destineInfo['create_time'] > 15*24*3600)
            {
                LogApi::debug("[appointmentRefund]预定时间必须在15个自然日内,预定创建时间".$destineInfo['create_time']);
                set_msg("预定时间必须在15个工作日内");
                return false;
            }
            //获取支付信息
            $pay_result =  OrderReturnRepository::getPayNo(OrderStatus::BUSINESS_DESTINE,$destineInfo['destine_no']);
            if(!$pay_result){
                LogApi::debug("[appointmentRefund]获取订单的支付信息失败",$pay_result);
                set_msg("获取订单的支付信息失败");
                return false;
            }

            //根据支付编号查找支付相关数据
            $payInfo =  PayQuery::getPaymentInfoByPaymentNo($pay_result['payment_no']);

            if (!isset($payInfo['out_payment_no']) || empty($payInfo['out_payment_no'])) {
                LogApi::error(__method__.'[appointmentRefund]财务发起退款申请前，发现out_payment_no失败：', $payInfo);
                return false;
            }
            $params = [
                'out_refund_no' => $destineInfo['destine_no'], //业务平台退款码
                'payment_no' => $payInfo['out_payment_no'], //支付平台支付码
                'amount' => $destineInfo['destine_amount'] * 100, //支付金额
                'refund_back_url' => config('ordersystem.ORDER_API') . '/appointmentRefund', //退款回调URL
            ];
            LogApi::info(__method__.'[appointmentRefund]财务发起退款请求前，请求的参数：', $params);

            $succss = CommonRefundApi::apply($params);
            LogApi::info(__method__.'[appointmentRefund]财务已经发起退款请求，请求后的参数及结果：',$succss);
            return true;



        }else{
            LogApi::debug("[appointmentRefund]预定状态必须是已支付，已下单,预定状态值".$destineInfo['destine_status']);
            //不允许退预定金
            return false;
        }


    }

    /***
     * 预定金15个自然日之后的退款
     * @param array $params
     *
     */
    public function refund(array $params){

        //获取预定信息
        $activityDestine = ActivityDestine::getByIdNo($params['id']);
        if(!$activityDestine){
            return false;
        }
        //修改预定的信息
        $updateActivityDestine=$activityDestine->updateActivityDestine($params);
        if(!$updateActivityDestine){
           return false;
        }
        return true;

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
    public static function callbackAppointment(array $params,array $userinfo){
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
        if($params['business_type'] != OrderStatus::BUSINESS_DESTINE){
            return false;
        }
        try{
            //获取预定信息
            $activityDestine = ActivityDestine::getByNo($params['business_no']);
            if(!$activityDestine){
                return false;
            }
           if($params['status'] == "success"){
               $updateRefund=$activityDestine->refund();
               if(!$updateRefund){
                   return false;
               }
               return true;
           }
        }catch (\Exception $exc) {
        LogApi::debug("程序异常",$exc);
        return false;

        }


    }
}