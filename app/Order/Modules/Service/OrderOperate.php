<?php
/**
 *    订单操作类
 *    author: heaven
 *    date : 2018-05-04
 */
namespace App\Order\Modules\Service;
use App\Lib\Certification;
use App\Lib\Common\JobQueueApi;
use App\Lib\Common\LogApi;
use App\Lib\Contract\Contract;
use App\Lib\Coupon\Coupon;
use App\Lib\Goods\Goods;
use App\Lib\Risk\Risk;
use App\Lib\Warehouse\Delivery;
use App\Order\Controllers\Api\v1\ReturnController;
use App\Order\Models\OrderDelivery;
use App\Order\Models\OrderExtend;
use App\Order\Models\OrderInsurance;
use App\Order\Models\OrderVisit;
use App\Order\Modules\Inc;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\PublicInc;
use App\Order\Modules\Repository\Order\DeliveryDetail;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderMiniRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\OrderRiskRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use App\Order\Modules\Repository\Pay\Channel;
use App\Order\Modules\Repository\Pay\WithholdQuery;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use Illuminate\Support\Facades\DB;
use App\Lib\Order\OrderInfo;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Log;


class OrderOperate
{
    /**
     * 订单发货接口
     * @param $orderDetail array
     * [
     *  'order_no'=>'',//订单编号
     *  'logistics_id'=>''//物流渠道ID
     *  'logistics_no'=>''//物流单号
     * ‘logistics_note’ //发货备注
     * ]
     * @param $goods_info array 商品信息 【必须】 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     * ]
     *@param $operatorInfo array 操作人员信息
     *
     *
     *
     * [
     *      'type'=>发货类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,//用户ID
     *      'user_name'=>1,//用户名
     * ]
     * @return boolean
     */

    public static function delivery($orderDetail,$goodsInfo,$operatorInfo=[]){
        DB::beginTransaction();
            //更新订单状态
            $order = Order::getByNo($orderDetail['order_no']);
            if(!$order){
                DB::rollBack();
                return false;
            }
            $orderInfo =$order->getData();
            //判断是否是订单发货
            if($orderInfo['freeze_type'] == Inc\OrderFreezeStatus::Non){
                //更新订单表状态
                $b=$order->deliveryFinish();
                if(!$b){
                    DB::rollBack();
                    return false;
                }

                //增加订单发货信息
                $b =DeliveryDetail::addOrderDelivery($orderDetail);
                if(!$b){
                    DB::rollBack();
                    return false;
                }

                //增加发货详情
                $b =DeliveryDetail::addGoodsDeliveryDetail($orderDetail['order_no'],$goodsInfo);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
                //增加发货时生成合同
               $b = DeliveryDetail::addDeliveryContract($orderDetail['order_no'],$goodsInfo);
                if(!$b) {
                    DB::rollBack();
                    return false;
                }
                //增加操作日志
                if(!empty($operatorInfo)){

                    OrderLogRepository::add($operatorInfo['user_id'],$operatorInfo['user_name'],$operatorInfo['type'],$orderDetail['order_no'],"发货",$orderDetail['logistics_note']);
                }
                DB::commit();
                //增加确认收货队列
                if($orderInfo['zuqi_type'] ==1){
                    $confirmTime = config('web.short_confirm_days');
                }else{
                    $confirmTime = config('web.long_confirm_days');
                }
                //订单确认收货队列
                $b =JobQueueApi::addScheduleOnce(config('app.env')."DeliveryReceive".$orderDetail['order_no'],config("ordersystem.ORDER_API")."/DeliveryReceive", [
                    'method' => 'api.inner.deliveryReceive',
                    'order_no'=>$orderDetail['order_no'],
                ],time()+$confirmTime,"");

                // 订单发货成功后 发送短信
                $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderDetail['order_no'],SceneConfig::ORDER_DELIVERY);
                $orderNoticeObj->notify();
                $orderNoticeObj->alipay_notify();

                return true;

            }else {
                //判断订单冻结类型 冻结就走换货发货
                $b = OrderReturnCreater::createchange($orderDetail, $goodsInfo,$operatorInfo);
                if (!$b) {
                    DB::rollBack();
                    return false;
                }
                DB::commit();
                return true;
            }

    }

    /**
     * 获取订单状态流
     * @param $orderNo 订单编号
     * @return array
     */


    public static function getOrderStatus($orderNo){
        $order = Order::getByNo($orderNo);
        $orderInfo = $order->getData();
        $res[] =['time' =>$orderInfo['create_time'],'status' =>"已下单"];
        if($orderInfo['pay_time']){
           $res[] =['time' =>$orderInfo['pay_time'],'status'=>"已支付"];
        }
        if($orderInfo['confirm_time']){
            $res[] =['time' =>$orderInfo['confirm_time'],'status'=>"已确认"];
        }
        if($orderInfo['delivery_time']){
            $res[] =['time' =>$orderInfo['delivery_time'],'status'=>"已发货"];
        }
        if($orderInfo['receive_time']){
            $res[] =['time' =>$orderInfo['receive_time'],'status'=>"租用中"];
        }
        if($orderInfo['order_status'] == Inc\OrderStatus::OrderCancel){
            $res[] =['time' =>$orderInfo['complete_time'],'status'=>"已取消（未支付）"];
            return $res;
        }
        if($orderInfo['order_status'] == Inc\OrderStatus::OrderClosedRefunded){
            $res[] =['time' =>$orderInfo['complete_time'],'status'=>"已关闭（已退款）"];
            return $res;
        }
        if($orderInfo['order_status'] == Inc\OrderStatus::OrderCompleted){
            $res[] =['time' =>$orderInfo['complete_time'],'status'=>"已完成"];
            return $res;
        }

        return $res;
    }

    /**
     *  增加订单出险/取消出险记录
     * @param $params
     * [
     *  'order_no'  => '',//订单编号
     *  'remark'=>'',//备注
     *  'type'=>'',// 类型 1出险 2取消出险
     * ]
     * @return array|bool
     */

    public static function orderInsurance($params)
    {
        DB::beginTransaction();
        try{
            //增加订单出险标识
            $extendData= [
                'order_no'=>$params['order_no'],
                'field_name'=>Inc\OrderExtendFieldName::FieldInsurance,
                'field_value'=>1,
            ];
            $res =OrderExtend::updateOrCreate($extendData);
            if(!$res->getQueueableId()){
                DB::rollBack();
                return false;
            }
            //增加出险记录
            $params['create_time'] =time();
            $order = OrderInsurance::updateOrCreate($params);
            $id =$order->getQueueableId();
            if(!$id){
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        }catch (\Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;

        }

    }


    /**
     * 获取保险操作信息
     * Author: heaven
     * @param $params
     * @return mixed
     */
    public static function getInsuranceInfo($params){

        $whereArray[] = ['order_no', '=', $params['order_no']];
        if (isset($params['goods_no'])) {
            $whereArray[] = ['goods_no', '=', $params['goods_no']];
        }


        $insuranceData =  OrderInsurance::where($whereArray)->get();
        $data = array();
        if ($insuranceData) {
            $data = $insuranceData->toArray();
            foreach($data as $keys=>$values) {
                $data[$keys]['typeName'] = Inc\OrderGoodStatus::getInsuranceTypeName($values['type']);
            }


        }
        return $data;

    }




    /**
     * 保存回访标识
     * @param $params
     * [
     *  'order_no'  => '',//订单编号
     *  'visit_id'=>'',//回访标识ID
     *  'visit_text'=>'',//回访备注
     * ]
     * @return array|bool
     */

    public static function orderVistSave($params)
    {
        DB::beginTransaction();
        try{
            //更新订单是否回访标识
            $res=OrderRepository::getOrderExtends($params['order_no'],Inc\OrderExtendFieldName::FieldVisit);
            if(empty($res)){
                $extendData= [
                    'order_no'=>$params['order_no'],
                    'field_name'=>Inc\OrderExtendFieldName::FieldVisit,
                    'field_value'=>1,
                ];
                $res =OrderExtend::create($extendData);
                $id = $res->getQueueableId();
                if(!$id){
                    DB::rollBack();
                    return false;
                }
            }
            //增加或更新 订单回访记录
            $params['create_time'] =time();
            $order = OrderVisit::updateOrCreate(['order_no'=>$params['order_no']],$params);
            $id =$order->getQueueableId();
            if(!$id){
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        }catch (\Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;

        }
    }

    /**
     * 获取订单日志接口
     * @param $orderNo
     * @return array|bool
     */

    public static function orderLog($orderNo)
    {
        if(empty($orderNo)){return false;}
        $logData = OrderLogRepository::getOrderLog($orderNo);
        if(!$logData){
            return false;
        }
        foreach ($logData as $k=>$v){

            $logData[$k]['operator_type_name'] = \App\Lib\PublicInc::getRoleName($v['operator_type']);
        }
        return $logData;
    }
    /**
     * 确认收货接口
     * @param  $system //0 前后端操作,1 自动执行任务
     * @param $params
     * [
     *      'order_no'=>''//订单编号
     *      'remark'=>''//备注
     * ]
     * @return boolean
     */

    public static function deliveryReceive($params,$system=0){
        $orderNo =$params['order_no'];
        $remark = isset($params['remark'])?$params['remark']:'';

        if(empty($orderNo)){set_msg("参数错误");return false;}
        DB::beginTransaction();
            //更新订单状态
            $order = Order::getByNo($orderNo);
            if(!$order){
                DB::rollBack();
                return false;
            }
            $b =$order->sign();
            if(!$b){
                DB::rollBack();
                return false;
            }

            $orderInfo = $order->getData();

            //查询订单 如果是长租 生成租期周期表 更新商品表
            if($orderInfo['zuqi_type'] ==2){
                //查询商品信息
                $goodsInfo = OrderRepository::getGoodsListByOrderId($orderNo);
                //更新商品表
                $goodsData['begin_time'] = time();
                $goodsData['end_time']=OrderOperate::calculateEndTime($goodsData['begin_time'],$goodsInfo[0]['zuqi']);
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($goodsInfo[0]['goods_no']);
                $b =$goods->updateGoodsServiceTime($goodsData);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
                //增加商品租期表
                $unitData =[
                    'order_no'=>$orderNo,
                    'goods_no'=>$goodsInfo[0]['goods_no'],
                    'user_id'=>$orderInfo['user_id'],
                    'unit'=>2,
                    'unit_value'=>$goodsInfo[0]['zuqi'],
                    'begin_time'=>$goodsData['begin_time'],
                    'end_time'=>$goodsData['end_time'],
                ];
                $b =ServicePeriod::createService($unitData);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
            }
            // 兼容老订单系统（短租服务时间为确认收货时间） 后期可以删除
            if($orderInfo['zuqi_type'] == 1){
                //查询商品信息
                $goodsInfo = OrderRepository::getGoodsListByOrderId($orderNo);
                foreach ($goodsInfo as $k=>$v){
                    if($goodsInfo[$k]['begin_time'] ==0 && $goodsInfo[$k]['end_time'] ==0){
                        //更新商品表
                        $goodsData['begin_time'] = time();
                        $goodsData['end_time']=OrderOperate::calculateEndTime($goodsData['begin_time'],$goodsInfo[$k]['zuqi']);
                        $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($goodsInfo[$k]['goods_no']);
                        $b =$goods->updateGoodsServiceTime($goodsData);
                        if(!$b){
                            DB::rollBack();
                            return false;
                        }

                        //增加商品租期表
                        $unitData =[
                            'order_no'=>$orderNo,
                            'goods_no'=>$goodsInfo[$k]['goods_no'],
                            'user_id'=>$orderInfo['user_id'],
                            'unit'=>1,
                            'unit_value'=>$goodsInfo[$k]['zuqi'],
                            'begin_time'=>$goodsData['begin_time'],
                            'end_time'=>$goodsData['end_time'],
                        ];
                        $b =ServicePeriod::createService($unitData);
                        if(!$b){
                            DB::rollBack();
                            return false;
                        }

                    }
                }

            }

            //更新订单商品的状态
            $b = OrderGoodsRepository::setGoodsInService($orderNo);
            if(!$b){
                DB::rollBack();
                return false;
            }

            if($system==1){
                $remark="系统自动执行任务";
                $userId =1;
                $userName ="系统";
                $userType =\App\Lib\PublicInc::Type_System;
            }else{
                $userInfo =$params['userinfo'];
                $userType =$userInfo['type']==1?\App\Lib\PublicInc::Type_User:\App\Lib\PublicInc::Type_Admin;
                $userId =$userInfo['uid'];
                $userName =$userInfo['username'];
            }
            //插入操作日志
            OrderLogRepository::add($userId,$userName,$userType,$orderNo,"确认收货",$remark);

            $params=[
                'order_no'=>$orderNo,//
                'receive_type'=>$userType,//类型：String  必有字段  备注：签收类型1管理员，2用户,3系统，4线下
                'user_id'=>$userId,//
                'user_name'=>$userName,//
            ];
            //LogApi::info("确认收货参数传递",$params);

            //通知给收发货系统
            $b =Delivery::orderReceive($params);
            if(!$b){
                DB::rollBack();
                return false;
            }

           // DB::commit();
            //签收后发送短信
            if($orderInfo['zuqi_type'] ==1){
                $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_DAY_RECEIVE);
                $orderNoticeObj->notify();
                $orderNoticeObj->alipay_notify();
            }else{
                $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_MONTH_RECEIVE);
                $orderNoticeObj->notify();
                $orderNoticeObj->alipay_notify();
            }
            //取消任务队列
            $cancel = JobQueueApi::cancel(config('app.env')."DeliveryReceive".$orderNo);
            DB::commit();
            return true;

    }
    private static function calculateEndTime($beginTime, $zuqi){
        $day = Inc\publicInc::calculateDay($zuqi);
        $endTime = $beginTime + $day*86400;
        return $endTime;
    }

    /**
     * 订单统计查询
     * @return array
     */

    public static function counted(){
        $arr =[];
        //退货待审核数量
        $arr['return_checking'] = OrderReturnRepository::returnCheckingCount();
        //待退款数量


        //待确认订单数量
        $arr['waiting_confirm'] = OrderRepository::getWaitingConfirmCount();



        return $arr;
    }

    /**
     * 后台确认订单操作
     * $data =[
     *   'order_no'  => '',//订单编号
     *   'remark'=>'',//操作备注
     *    'userinfo'
     * ]
     *  $userinfo [
     *  'uid'=>'',
     *  'mobile'=>'',
     *  'type'=>'',
     *  'username'=>'',
     *
     * ]
     * @return boolean
     */

    public static function confirmOrder($data){
        if(empty($data)){return false;}
        DB::beginTransaction();
        try{
            //更新订单状态
            $order = Order::getByNo($data['order_no']);
            if(!$order){
                DB::rollBack();
                return false;
            }
            $b =$order->deliveryOpen($data['remark']);
            if(!$b){
                DB::rollBack();
                return false;
            }
            $goodsInfo = OrderRepository::getGoodsListByOrderId($data['order_no']);
            $orderInfo = OrderRepository::getOrderInfo(['order_no'=>$data['order_no']]);
            $orderInfo['business_key'] = Inc\OrderStatus::BUSINESS_ZUJI;
            $orderInfo['business_no'] =$data['order_no'];
            $orderInfo['order_no']=$data['order_no'];
            $delivery =Delivery::apply($orderInfo,$goodsInfo);
            if(!$delivery){
                DB::rollBack();
                return false;
            }
            $userInfo =$data['userinfo'];
            OrderLogRepository::add($userInfo['uid'],$userInfo['username'],\App\Lib\PublicInc::Type_Admin,$data['order_no'],"确认订单","后台申请发货");

            DB::commit();
            return true;
        }catch (\Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;

        }

    }
    /**
     * 取消订单
     * Author: heaven
     * @param $orderNo 订单编号
     * @param string $userId 用户id
     * @return bool|string
     */
    public static function cancelOrder($orderNo,$userId='',$reasonId = '')
    {
        if (empty($orderNo)) {
            return  ApiStatus::CODE_31001;
        }
        //查询订单的状态
        $orderInfoData =  OrderRepository::getInfoById($orderNo,$userId);

        if ($orderInfoData['order_status']!=Inc\OrderStatus::OrderWaitPaying)  return  ApiStatus::CODE_31007;
        //开启事物
        DB::beginTransaction();
        try {

            //关闭订单状态
            $orderData =  OrderRepository::closeOrder($orderNo,$userId);
            if (!$orderData) {
                DB::rollBack();
                return ApiStatus::CODE_31002;
            }


            //分期关闭
            //查询分期
            $isInstalment   =   OrderGoodsInstalmentRepository::queryCount(['order_no'=>$orderNo]);
            if ($isInstalment) {
                $success =  Instalment::close(['order_no'=>$orderNo]);
                if (!$success) {
                    DB::rollBack();
                    return ApiStatus::CODE_31004;
                }
            }

            //释放库存
            //查询商品的信息
            $orderGoods = OrderRepository::getGoodsListByOrderId($orderNo);
            if ($orderGoods) {
                foreach ($orderGoods as $orderGoodsValues){
                    //暂时一对一
                    $goods_arr[] = [
                        'sku_id'=>$orderGoodsValues['zuji_goods_id'],
                        'spu_id'=>$orderGoodsValues['prod_id'],
                        'num'=>$orderGoodsValues['quantity']
                    ];
                }
                $success =Goods::addStock($goods_arr);
            }

            if (!$success || empty($orderGoods)) {
                DB::rollBack();
                return ApiStatus::CODE_31003;
            }

            //支付方式为代扣 需要解除订单代扣
            if($orderInfoData['pay_type'] == Inc\PayInc::WithhodingPay){
                //查询是否签约代扣 如果签约 解除代扣
                try{
                    $withhold = WithholdQuery::getByBusinessNo(Inc\OrderStatus::BUSINESS_ZUJI,$orderNo);
                    $params =[
                        'business_type' =>Inc\OrderStatus::BUSINESS_ZUJI,	// 【必须】int		业务类型
                        'business_no'	=>$orderNo,	// 【必须】string	业务编码
                    ];
                    $b =$withhold->unbind($params);
                    if(!$b){
                        DB::rollBack();
                        return ApiStatus::CODE_31008;
                    }

                }catch (\Exception $e){
                    //未签约 不解除
                }

            }

            //优惠券归还
            //通过订单号获取优惠券信息
            $orderCouponData = OrderRepository::getCouponListByOrderId($orderNo);

            if ($orderCouponData) {
                $coupon_id = array_column($orderCouponData, 'coupon_id');
                $success =  Coupon::setCoupon(['user_id'=>$userId ,'coupon_id'=>$coupon_id]);

                if ($success) {
                    DB::rollBack();
                    return ApiStatus::CODE_31003;
                }

            }


            DB::commit();
            //取消定时任务
            $cancel = JobQueueApi::cancel(config('app.env')."OrderCancel_".$orderNo);
            // 订单取消后发送取消短息。;
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_CANCEL);
            $orderNoticeObj->notify();
            return ApiStatus::CODE_0;

        } catch (\Exception $exc) {
            DB::rollBack();
            return  ApiStatus::CODE_31006;
        }

    }

    /**
     * 获取风控和认证信息
     * @param $orderNo
     * @return array
     */
    public static function getOrderRisk($orderNo){

        //获取认证信息
        $orderCertified = OrderUserCertifiedRepository::getUserCertifiedByOrder($orderNo);
        $arr =[];
        $riskArray =[];
        if(!empty($orderCertified)){
            $arr['name'] = '认证平台';
            $arr['value'] = Certification::getPlatformName($orderCertified['certified_platform']);
            $riskArray[]=$arr;
            $arr['name'] = '平台信用分';
            $arr['value'] = $orderCertified['credit'];
            $riskArray[]=$arr;
        }
        //获取风控系统信息
        $orderRisk =OrderRiskRepository::getRisknfoByOrderNo($orderNo);
        if($orderRisk){
            foreach ($orderRisk as $k=>$v){
                if($v['type'] == Risk::RiskYidun){
                    $arr['name'] = '蚁盾分数';
                    $arr['value'] = $v['score'];
                    $riskArray[]=$arr;
                }
                if($v['type'] == Risk::RistZhimaScore){
                    $arr['name'] = '芝麻分数';
                    $arr['value'] = $v['score'];
                    $riskArray[]=$arr;
                    $arr['name'] = '芝麻等级';
                    $arr['value'] = !empty(Risk::getDecisionName($v['decision']))?Risk::getDecisionName($v['decision']):$v['decision'];
                    $riskArray[]=$arr;
                    continue;
                }
                if($v['type'] == Risk::RistScore){
                    $arr['name'] = '风控系统分';
                    $arr['value'] = $v['score'];
                    $riskArray[]=$arr;
                    continue;
                }
                $arr['name'] = Risk::getRiskName($v['type']);
                $arr['value'] = !empty(Risk::getDecisionName($v['decision']))?Risk::getDecisionName($v['decision']):$v['decision'];
                $riskArray[]=$arr;

            }
        }

        if(!$orderRisk){
            $arr['name'] = '风控数据';
            $arr['value'] = '暂无';
            $riskArray[]=$arr;
        }

        return $riskArray;
    }
    /**
     * 查询订单的商品的状态是否全部完成 完成后更新状态
     * @param $orderNo 订单编号
     * @return boolean
     */
    public static function isOrderComplete($orderNo){
        //查询订单商品信息
        $goods = OrderGoodsRepository::getGoodsByOrderNo($orderNo);
        if(!$goods){
            return false;
        }
        $goods = objectToArray($goods);
        $orderStatus =0;
        foreach ($goods as $k=>$v){
            //查询是否有 未还机 未退款 未买断 订单就是未结束的 就返回
            if($v['goods_status'] == Inc\OrderGoodStatus::REFUNDED || $v['goods_status'] == Inc\OrderGoodStatus::EXCHANGE_REFUND){
                $orderStatus = Inc\OrderStatus::OrderClosedRefunded;
            }
            if($v['goods_status'] == Inc\OrderGoodStatus::COMPLETE_THE_MACHINE || $v['goods_status'] == Inc\OrderGoodStatus::BUY_OUT){
                $orderStatus = Inc\OrderStatus::OrderCompleted;
            }
            if($v['goods_status']!=Inc\OrderGoodStatus::REFUNDED && $v['goods_status']!=Inc\OrderGoodStatus::COMPLETE_THE_MACHINE && $v['goods_status']!=Inc\OrderGoodStatus::BUY_OUT && $v['goods_status'] != Inc\OrderGoodStatus::EXCHANGE_REFUND){
            //var_dump("订单未完成");die;
                return true;
            }
        }
        if($orderStatus!=0){
            //如果订单完成 更新订单状态
            $order = Order::getByNo($orderNo);
            $b =$order->updateStatus($orderStatus,0);
            if(!$b){
                return false;
            }
            $orderInfo =$order->getData();
            //解除代扣的订单绑定
            $b =self::orderUnblind($orderInfo);
            if(!$b){
                return false;
            }
            //增加操作日志
            OrderLogRepository::add(0,"系统",\App\Lib\PublicInc::Type_System,$orderNo,Inc\OrderStatus::getStatusName($orderStatus),"订单结束");

        }
        return true;
    }

    /**
     * 根据支付方式 解除订单代扣信息
     * @param $orderInfo 订单信息
     * @return bool
     */

    public static function orderUnblind($orderInfo){
        //支付方式为代扣 需要解除订单代扣
        if($orderInfo['pay_type'] == Inc\PayInc::WithhodingPay){
            //查询是否签约代扣 如果签约 解除代扣
            try{
                $withhold = WithholdQuery::getByBusinessNo(Inc\OrderStatus::BUSINESS_ZUJI,$orderInfo['order_no']);
                $params =[
                    'business_type' =>Inc\OrderStatus::BUSINESS_ZUJI,	// 【必须】int		业务类型
                    'business_no'	=>$orderInfo['order_no'],	// 【必须】string	业务编码
                ];
                $b =$withhold->unbind($params);
                if(!$b){
                    return false;
                }

            }catch (\Exception $e){
                //未签约 不解除
            }

        }
        return true;
    }



    /**
     * 生成订单号
     * Author: heaven
     * @param int $orderType
     * @return string
     */
    public static function createOrderNo($orderType=1){
        $year = array();
        for($i=65;$i<91;$i++){
            $year[]= strtoupper(chr($i));
        }
        $orderSn = $year[(intval(date('Y')))-2018] . strtoupper(dechex(date('m'))) . date('d') .$orderType. substr(time(), -5) . substr(microtime(), 2, 5) . rand(0, 9);
        return $orderSn;
    }

    /**
     * 获取订单详情
     * Author: heaven
     * @param $orderNo
     * @return array|\Illuminate\Http\JsonResponse
     */
    public static function getOrderInfo($orderNo)
    {
        $order = array();
        if (empty($orderNo))   return apiResponse([],ApiStatus::CODE_32001,ApiStatus::$errCodes[ApiStatus::CODE_32001]);
        //查询订单和用户发货的数据
        $orderData =  OrderRepository::getOrderInfo(array('order_no'=>$orderNo));
        if (empty($orderData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        //分期数据
        $goodsExtendData =  OrderGoodsInstalment::queryList(array('order_no'=>$orderNo));

        $orderData['instalment_unpay_amount'] = 0.00;
        $orderData['instalment_payed_amount'] = 0.00;
        $goodsExtendArray = array();
        if ($goodsExtendData) {
            $instalmentUnpayAmount  = 0.00;
            $instalmentPayedAmount  = 0.00;
            foreach ($goodsExtendData as $goodsKeys=>$goodsValues) {
                if (is_array($goodsValues)) {
                    foreach($goodsValues as $keys=>$values) {
                        $goodsExtendData[$goodsKeys][$keys]['payment_time']   = $values['payment_time'] ? date("Y-m-d H:i:s",$values['payment_time']) : "";
                        $goodsExtendData[$goodsKeys][$keys]['update_time']    = $values['update_time'] ? date("Y-m-d H:i:s",$values['update_time']) : "";
                        $goodsExtendData[$goodsKeys][$keys]['withhold_time']  = withholdDate($values['term'], $values['day']);
                        if ($values['times']==1)
                        {
                            $goodsExtendArray[$values['goods_no']]['firstAmount'] =$values['amount'];
                            $goodsExtendArray[$values['goods_no']]['firstInstalmentDate'] = withholdDate($values['term'], $values['day']);
                        }


                        if ($values['status']==Inc\OrderInstalmentStatus::SUCCESS)
                        {


                            $instalmentPayedAmount+=$values['amount'];
                        } else {

                            $instalmentUnpayAmount+=$values['amount'];
                        }

                        $goodsExtendData[$goodsKeys][$keys]['status']         = \App\Order\Modules\Inc\OrderInstalmentStatus::getStatusName($values['status']);
                    }

                }
            }



            //未支付总金额
            $orderData['instalment_unpay_amount'] = normalizeNum($instalmentUnpayAmount);
            //已支付总金额
            $orderData['instalment_payed_amount'] = normalizeNum($instalmentPayedAmount);
        }
        
        $order['instalment_info'] = $goodsExtendData;

        //订单状态名称
        $orderData['order_status_name'] = Inc\OrderStatus::getStatusName($orderData['order_status']);

        //支付方式名称
        $orderData['pay_type_name'] = Inc\PayInc::getPayName($orderData['pay_type']);

        //应用来源
        $orderData['appid_name'] = OrderInfo::getAppidInfo($orderData['appid']);
        $orderData['zm_order_no']    =  '';
        //获取小程序芝麻单号
        if ($orderData['order_type']==Inc\OrderStatus::orderMiniService) {

            $miniOrderData = OrderMiniRepository::getMiniOrderInfo($orderNo);

            $orderData['zm_order_no']    =    $miniOrderData['zm_order_no'];

        }


        //订单金额
        $orderData['order_gooods_amount']  = $orderData['order_amount']+$orderData['coupon_amount']+$orderData['discount_amount']+$orderData['order_insurance'];
        //支付金额
        $orderData['pay_amount']  = $orderData['order_amount']+$orderData['order_insurance'];
        //总租金
        $orderData['zujin_amount']  =   $orderData['order_amount'];
        //碎屏意外险
        $orderData['order_insurance_amount']  =   $orderData['order_insurance'];
        //授权总金额
        $orderData['yajin_amount']  =   $orderData['order_yajin'];

        $orderData['certified_platform_name']  =   Certification::getPlatformName($orderData['certified_platform']);

        $order['order_info'] = $orderData;

        //订单商品列表相关的数据
        $actArray = Inc\OrderOperateInc::orderInc($orderData['order_status'], 'actState');

        $goodsData =  self::getGoodsListActState($orderNo, $actArray, $goodsExtendArray);

        if (empty($goodsData)) return apiResponseArray(ApiStatus::CODE_32002,[]);

        $order['goods_info'] = $goodsData;
        //设备扩展信息表
        $goodsExtendData =  self::getOrderDeliveryInfo($orderNo);
        $order['goods_extend_info'] = $goodsExtendData;
        //优惠券信息
        $couponInfo =    OrderRepository::getCouponByOrderNo($orderNo);
        $order['coupon_info'] =   $couponInfo;
        return apiResponseArray(ApiStatus::CODE_0,$order);

    }


    /**
     * 获取物流信息
     * @param string $order_no
     * @param string $goods_no
     * @return DeliveryDetail|bool
     */
    public static function getOrderDeliveryInfo(string $order_no){
        $builder=OrderDelivery::where([['order_no','=',$order_no]])->limit(1);
        $order_delivery_info = $builder->first();
        if( !$order_delivery_info ){
            return false;
        }
        return $order_delivery_info->toArray();
    }

    /**
     * 获取客户端订单列表
     * Author: heaven
     * @param array $param
     * @return array
     */
    public static function getClientOrderList($param = array())
    {
        //根据用户id查找订单列表

        $newParam =  $param['params'];

        $newParam['uid']=  $param['userinfo']['uid'];


        $orderList = OrderRepository::getClientOrderList($newParam);

        $orderListArray = objectToArray($orderList);

        if (!empty($orderListArray['data'])) {

            foreach ($orderListArray['data'] as $keys=>$values) {



                //订单状态名称
                $orderListArray['data'][$keys]['order_status_name'] = Inc\OrderStatus::getStatusName($values['order_status']);
                //支付方式名称
                $orderListArray['data'][$keys]['pay_type_name'] = Inc\PayInc::getPayName($values['pay_type']);
                //应用来源
                $orderListArray['data'][$keys]['appid_name'] = OrderInfo::getAppidInfo($values['appid']);



                //设备名称

                //订单商品列表相关的数据
                $actArray = Inc\OrderOperateInc::orderInc($values['order_status'], 'actState');


                $goodsData =  self::getGoodsListActState($values['order_no'], $actArray);

                $orderListArray['data'][$keys]['goodsInfo'] = $goodsData;

//                $orderListArray['data'][$keys]['admin_Act_Btn'] = Inc\OrderOperateInc::orderInc($values['order_status'], 'adminActBtn');
                //回访标识
//                $orderListArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? Inc\OrderStatus::getVisitName($values['visit_id']):Inc\OrderStatus::getVisitName(Inc\OrderStatus::visitUnContact);

               $orderOperateData  = self::getOrderOprate($values['order_no']);

                $orderListArray['data'][$keys]['act_state'] = $orderOperateData['button_operate'] ?? $orderOperateData['button_operate'];
                $orderListArray['data'][$keys]['logistics_info'] = $orderOperateData['logistics_info'] ?? $orderOperateData['logistics_info'];
                $orderListArray['data'][$keys]['zm_order_no'] = $orderOperateData['zm_order_no'] ?? $orderOperateData['zm_order_no'];
//                if ($values['order_status']==Inc\OrderStatus::OrderWaitPaying) {
//                    $params = [
//                    'payType' => $values['pay_type'],//支付方式 【必须】<br/>
//                    'payChannelId' => Channel::Alipay,//支付渠道 【必须】<br/>
//                    'userId' => $param['userinfo']['uid'],//业务用户ID<br/>
//                    'fundauthAmount' => $values['order_yajin'],//Price 预授权金额，单位：元<br/>
//
//                    'business_key' => Inc\OrderStatus::BUSINESS_ZUJI,//Price 预授权金额，单位：元<br/>
//                    'business_no' => $values['order_no'],//Price 预授权金额，单位：元<br/>
//	        ];
////                    LogApi::debug('客户端订单列表支付信息参数', $params);
//                    $orderListArray['data'][$keys]['payInfo'] = self::getPayStatus($params);
////                    LogApi::debug('客户端订单列表支付信息返回的值', $orderListArray['data'][$keys]['payInfo']);
//                }

            }

        }

        return apiResponseArray(ApiStatus::CODE_0,$orderListArray);


    }

    /**
     * 获取后台订单列表
     * Author: heaven
     * @param array $param
     * @return array
     */
    public static function getOrderList($param = array())
    {
        //根据用户id查找订单列表

        $orderList = OrderRepository::getOrderList($param);

        $orderListArray = objectToArray($orderList);

        if (!empty($orderListArray['data'])) {

            foreach ($orderListArray['data'] as $keys=>$values) {

                //订单状态名称
                $orderListArray['data'][$keys]['order_status_name'] = Inc\OrderStatus::getStatusName($values['order_status']);
                //支付方式名称
                $orderListArray['data'][$keys]['pay_type_name'] = Inc\PayInc::getPayName($values['pay_type']);
                //应用来源
                $orderListArray['data'][$keys]['appid_name'] = OrderInfo::getAppidInfo($values['appid']);
                //订单冻结名称
                $orderListArray['data'][$keys]['freeze_type_name'] = Inc\OrderFreezeStatus::getStatusName($values['freeze_type']);

                //设备名称

                //订单商品列表相关的数据
                $actArray = Inc\OrderOperateInc::orderInc($values['order_status'], 'adminActBtn');


                $goodsData =  self::getManageGoodsActAdminState($values['order_no'], $actArray);

                $orderListArray['data'][$keys]['goodsInfo'] = $goodsData;

				// 有冻结状态时
                if ($values['freeze_type']>0) {
                    $actArray['refund_btn'] = false;
                    $actArray['modify_address_btn'] = false;
                    $actArray['confirm_btn'] = false;
                    $actArray['confirm_receive'] = false;
                    $actArray['buy_off'] = false;
                    $actArray['Insurance'] = false;
                }

                $orderListArray['data'][$keys]['admin_Act_Btn'] = $actArray;
                //回访标识
                $orderListArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? Inc\OrderStatus::getVisitName($values['visit_id']):Inc\OrderStatus::getVisitName(Inc\OrderStatus::visitUnContact);

                //$orderListArray['data'][$keys]['act_state'] = self::getOrderOprate($values['order_no']);

            }

        }

        return apiResponseArray(ApiStatus::CODE_0,$orderListArray);


    }


    /**
     * 根据订单号查询订单可操作的列表
     * Author: heaven
     * @param $orderNo
     */
    public static function getOrderOprate($orderNo)
    {
        if (empty($orderNo)) return [];
        $actArray = [];

        $list = array();

        $orderData   =  self::getOrderInfo($orderNo);

        $orderData  =   $orderData['data'];
        if (empty($orderData['order_info'])) return [];
        $actArray   =   Inc\OrderOperateInc::orderInc($orderData['order_info']['order_status'], 'actState');

        //处于租用中订单上无操作按钮
        if ($orderData['order_info']['order_status'] == Inc\OrderStatus::OrderInService) {
            $actArray['service_btn'] = false;
            $actArray['expiry_process'] = false;
            $actArray['prePay_btn'] = false;

        }
        if ($orderData['order_info']['freeze_type'] >0) {
            $actArray['cancel_pay_btn'] = false;
        }
        $list['zm_order_no'] = $orderData['order_info']['zm_order_no'];
        $list['button_operate'] = $actArray;
        $list['logistics_info'] = $orderData['goods_extend_info'];

        return $list;

    }


    /**
     * 获取客户端设置的操作列表
     * Author: heaven
     * @param $orderNo
     * @param $actArray
     * @return array|bool
     */
   public static function getGoodsListActState($orderNo, $actArray, $goodsExtendArray=array())
   {

       $goodsList = OrderRepository::getGoodsListByOrderId($orderNo);

       if (empty($goodsList)) return [];
           //到期时间多于1个月不出现到期处理
           foreach($goodsList as $keys=>$values) {
               $goodsList[$keys]['specs'] = filterSpecs($values['specs']);
               $goodsList[$keys]['left_zujin'] = '';
               if ($goodsExtendArray) {

                   $goodsList[$keys]['firstAmount'] = $goodsExtendArray[$values['goods_no']]['firstAmount'];
                   $goodsList[$keys]['firstInstalmentDate'] = $goodsExtendArray[$values['goods_no']]['firstInstalmentDate'];

               }

               //处于租期中，获取剩余未支付租金
               if($values['goods_status']>=Inc\OrderGoodStatus::RENTING_MACHINE) {
                   $where = array();
                   $where[] = ['status','=', \App\Order\Modules\Inc\OrderInstalmentStatus::UNPAID];
                   $where[] = ['goods_no','=',$values['goods_no']];
                   $instaulment = OrderGoodsInstalmentRepository::getSumAmount($where);
                   if ($instaulment){

                       $goodsList[$keys]['left_zujin'] = $instaulment['amount'];
                   }

               }




               //显示花期还款总金额及每月支付金额
               $repaymentAmount =   normalizeNum($values['amount_after_discount']+$values['insurance']);

               $goodsList[$keys]['repayment_amount'] =  $repaymentAmount;
               $zujinInsurance =   normalizeNum($values['zujin']+$values['insurance']);
               $goodsList[$keys]['zujin_Insurance'] =  $zujinInsurance;
               if ($values['zuqi_type']==Inc\OrderStatus::ZUQI_TYPE_DAY) {


                   $goodsList[$keys]['repayment_month'] =   $repaymentAmount;

               } else {

                   $goodsList[$keys]['repayment_month'] =   normalizeNum($repaymentAmount/$values['zuqi']);
               }
               $goodsList[$keys]['less_yajin'] = normalizeNum($values['goods_yajin']-$values['yajin']);
               $isBuyOut = $values['goods_status']>=Inc\OrderGoodStatus::BUY_OFF && $values['goods_status']<Inc\OrderGoodStatus::RELET;
               $goodsList[$keys]['is_buyout'] = $isBuyOut ?? 0;

               $isGiveBack = $values['goods_status']>=Inc\OrderGoodStatus::BACK_IN_THE_MACHINE && $values['goods_status']<Inc\OrderGoodStatus::BUY_OFF;
               $goodsList[$keys]['is_giveback'] = $isGiveBack ?? 0;

               $goodsList[$keys]['market_zujin'] = normalizeNum($values['amount_after_discount']+$values['coupon_amount']+$values['discount_amount']);
               if (empty($actArray)){
                   $goodsList[$keys]['act_goods_state']= [];
               } else {

                   $goodsList[$keys]['act_goods_state']= $actArray;
                   /**
                    * 短租：
                    *   申请售后没有
                    *   到期处理：当天到期申请，有售后的
                    *
                    */
                   if ($values['zuqi_type']== Inc\OrderStatus::ZUQI_TYPE1) {

                       //申请售后没有
                       $goodsList[$keys]['act_goods_state']['service_btn'] = false;
                       //到期处理
                       if ($values['end_time']>time()+config('web.day_expiry_process_days')) {
                           $goodsList[$keys]['act_goods_state']['expiry_process'] = false;
                       }

                   } else {

                       /**
                        * 长租：
                        *   申请售后：7天内
                        *   到期处理：快到期1个月内
                        */
                       //超过7天不出现售后
                       if ($values['begin_time'] > 0 &&  ($values['begin_time'] + config('web.month_service_days')) < time()) {
                           $goodsList[$keys]['act_goods_state']['service_btn'] = false;
                       }

                       //不在一个月内不出现到期处理
                       if ($values['end_time'] > 0 && ($values['end_time'] - config('web.month_expiry_process_days')) > time()) {
                           $goodsList[$keys]['act_goods_state']['expiry_process'] = false;
                       }
                   }

                   //无分期或者分期已全部还完不出现提前还款按钮
                   $orderInstalmentData = OrderGoodsInstalment::queryList(array('order_no'=>$orderNo,'goods_no'=>$values['goods_no'],  'status'=>Inc\OrderInstalmentStatus::UNPAID));
                   if (empty($orderInstalmentData)){
                       $goodsList[$keys]['act_goods_state']['prePay_btn'] = false;
                   }

                   //查询是否有提前还款操作
                   $aheadInfo = OrderBuyout::getAheadInfo($orderNo, $values['goods_no']);
                   if ($aheadInfo && $values['goods_status']==Inc\OrderGoodStatus::RENTING_MACHINE) {
                       $goodsList[$keys]['act_goods_state']['ahead_buyout'] = true;
                   }
                   //查询是否有还机去支付
                   $giveBackParam = [
                       'order_no' => $orderNo,
                       'goods_no' => $values['goods_no'],
                   ];

                   $giveBackData = OrderGiveback::getNeedpayInfo($giveBackParam);
                   if (!empty($giveBackData) && is_array($giveBackData)) {
                       $goodsList[$keys]['act_goods_state']['giveback_topay'] = true;
                   }

                   //查询用户是否有买断的去支付
                   $buyoutInfo = OrderBuyout::getAheadInfo($orderNo, $values['goods_no'],0,0);
                   if ($buyoutInfo) {
                       $goodsList[$keys]['act_goods_state']['buyout_topay'] = true;
                   }

                  $isAllowReturn = OrderReturnCreater::allowReturn(['order_no'=>$orderNo, 'goods_no'=>$values['goods_no']]);


//                  LogApi::info("获取OrderReturnCreater::allowReturn的结果".$orderNo."商品号".$values['goods_no'],$isAllowReturn);

                   $goodsList[$keys]['is_allow_return'] = ($isAllowReturn && !is_array($isAllowReturn)) ?? 0;

                   $isReturnBtn = $values['goods_status']>=Inc\OrderGoodStatus::REFUNDS && $values['goods_status']<=Inc\OrderGoodStatus::REFUNDED;
//                   LogApi::info("获取OrderGoods的退货状态".$orderNo."商品号".$values['goods_no'],$isReturnBtn);
//                   LogApi::info("获取是否是退货".(is_array($isAllowReturn) && !empty($isAllowReturn) && $isAllowReturn['data']['business_key']==Inc\OrderStatus::BUSINESS_RETURN));
                   $goodsList[$keys]['is_return_btn'] = ($isReturnBtn || (is_array($isAllowReturn) && !empty($isAllowReturn) && $isAllowReturn['data']['business_key']==Inc\OrderStatus::BUSINESS_RETURN)) ?? 0;
//                   LogApi::info("获取OrderGoods的退货按钮状态".$orderNo."商品号".$values['goods_no'],$goodsList[$keys]['is_return_btn']);
                   $isExchange  = $values['goods_status']>=Inc\OrderGoodStatus::EXCHANGE_GOODS && $values['goods_status']<=Inc\OrderGoodStatus::EXCHANGE_OF_GOODS;
//                   LogApi::info("获取是否是换货".(is_array($isAllowReturn) && !empty($isAllowReturn) && $isAllowReturn['data']['business_key']==Inc\OrderStatus::BUSINESS_BARTER));
//                   LogApi::info("获取OrderGoods的换货状态".$orderNo."商品号".$values['goods_no'],$isExchange);
                   $goodsList[$keys]['is_exchange_btn'] = ($isExchange || (is_array($isAllowReturn) && !empty($isAllowReturn) && $isAllowReturn['data']['business_key']==Inc\OrderStatus::BUSINESS_BARTER))?? 0;

               }

           }

       return $goodsList;


   }





    /**
     * 获取后台设置的操作列表
     * Author: heaven
     * @param $orderNo
     * @param $actArray
     * @return array|bool
     */
    public static function getManageGoodsActAdminState($orderNo, $actArray)
    {

        $goodsList = OrderRepository::getGoodsListByOrderId($orderNo);
        if (empty($goodsList)) return [];

        //到期时间多于1个月不出现到期处理
        foreach($goodsList as $keys=>$values) {
            $goodsList[$keys]['less_yajin'] = normalizeNum($values['goods_yajin']-$values['yajin']);
            $goodsList[$keys]['specs'] = filterSpecs($values['specs']);
            $goodsList[$keys]['market_zujin'] = normalizeNum($values['amount_after_discount']+$values['coupon_amount']+$values['discount_amount']);
            if (empty($actArray)){
                $goodsList[$keys]['act_goods_state']= [];
            } else {

                $goodsList[$keys]['act_goods_state']= $actArray;
                //是否处于售后之中

                $expire_process = intval($values['goods_status']) >= Inc\OrderGoodStatus::EXCHANGE_GOODS ?? false;
                if ($expire_process) {
                    $goodsList[$keys]['act_goods_state']['buy_off'] = false;
                    $goodsList[$keys]['act_goods_state']['refund_btn'] = false;
                    $goodsList[$keys]['act_goods_state']['modify_address_btn'] = false;
                    $goodsList[$keys]['act_goods_state']['confirm_btn'] = false;
                    $goodsList[$keys]['act_goods_state']['confirm_receive'] = false;
                    $goodsList[$keys]['act_goods_state']['Insurance'] = false;
                 
                }
                //是否已经操作过保险

                $insuranceData = self::getInsuranceInfo(['order_no'  => $orderNo , 'goods_no'=>$values['goods_no']]);
//                $orderInstalmentData = OrderGoodsInstalment::queryList(array('order_no'=>$orderNo,'goods_no'=>$values['goods_no'],  'status'=>Inc\OrderInstalmentStatus::UNPAID));
                if ($insuranceData){
                    $goodsList[$keys]['act_goods_state']['Insurance'] = false;
                    $goodsList[$keys]['act_goods_state']['alreadyInsurance'] = true;
                    $popInsurance = array_pop($insuranceData);
                    if ($popInsurance['type'] == 2) {
                        $goodsList[$keys]['act_goods_state']['alreadyInsurance'] = false;
                        $goodsList[$keys]['act_goods_state']['Insurance'] = true;
                    }

                    $goodsList[$keys]['act_goods_state']['insuranceDetail'] = true;
                }

            }

        }

        return $goodsList;


    }

    /**
     *
     * 根据订单号获取商品列表信息
     * Author: heaven
     * @param $orderNo
     * @return array|bool
     */
        public static function getGoodsListByOrderNo($orderNo)
        {
            return  OrderRepository::getGoodsListByOrderId($orderNo);

        }
	/**
	 * 获取订单支付单状态列表
	 * @param array $param 创建支付单数组
	 * $param = [<br/>
	 		'bussiness_key' => '',//业务类型 【必须】<br/>
	 		'bussiness_no' => '',//业务编号 【必须】<br/>
	 		'payType' => '',//支付方式 【必须】<br/>
	 		'payChannelId' => '',//支付渠道 【必须】<br/>
			'userId' => 'required',//业务用户ID<br/>
			'fundauthAmount' => 'required',//Price 预授权金额，单位：元<br/>
	 * ]<br/>
	 * @return mixed boolen：flase创建失败|array $result 结果数组
	 * $result = [<br/>
	 *		'withholdStatus' => '',是否需要签代扣（true：需要签约代扣；false：无需签约代扣）//<br/>
	 *		'paymentStatus' => '',是否需要支付（true：需要支付；false:无需支付）//<br/>
	 *		'fundauthStatus' => '',是否需要预授权（true：需要预授权；false：无需预授权）//<br/>
	 * ]
	 */
	public static function getPayStatus( $param ) {
		//-+--------------------------------------------------------------------
		// | 从新修改次方法 【开始】
		//-+--------------------------------------------------------------------
		if( empty( $param['business_key'] ) || empty( $param['business_no'] ) ){
			throw new \Exception('支付状态获取失败参数出错');
		}
		
		//获取支付单信息
		$payInfo = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($param['business_key'], $param['business_no']);
		if( $payInfo->isSuccess() ){
			return [
				'withholdStatus' => false,
				'paymentStatus' => false,
				'fundauthStatus' => false,
			];
		}
		return [
			'withholdStatus' => $payInfo->needWithhold(),
			'paymentStatus' => $payInfo->needPayment(),
			'fundauthStatus' => $payInfo->needFundauth(),
		];
		//-+--------------------------------------------------------------------
		// | 从新修改次方法 【结束，下面内容没有用】
		//-+--------------------------------------------------------------------
		throw new \Exception('支付状态获取失败');
		
		
		
		//-+--------------------------------------------------------------------
		// | 校验参数
		//-+--------------------------------------------------------------------
        LogApi::debug('客户端getPayStatus参数', $param);
		if( !self::__praseParam($param) ){
			return false;
		}
		
		//-+--------------------------------------------------------------------
		// | 判断租金支付方式（分期/代扣）
		//-+--------------------------------------------------------------------
		$result = false;
		//代扣方式支付租金
		if( $param['payType'] == PayInc::WithhodingPay ){
			//然后判断预授权然后创建相关支付单
			$result = self::__withholdFundAuth($param);
            LogApi::debug('客户端getPayStatus代扣方式支付租金返回结果', $result);
			//分期支付的状态为false
			$data['paymentStatus'] = false;
		}
		//分期方式支付租金
		elseif( $param['payType'] = PayInc::FlowerStagePay || $param['payType'] = PayInc::UnionPay ){
			//然后判断预授权然后创建相关支付单
			$result = self::__paymentFundAuth($param);
            LogApi::debug('客户端getPayStatus分期方式支付租金返回结果', $result);
			//代扣支付的状态为false
			$data['withholdStatus'] = false;
			//代扣支付的状态为false
			$data['paymentStatus'] = true;
		}
		//暂无其他支付
		else{
			return false;
		}
		//判断支付单创建结果
		if( !$result ){
			return false;
		}
        LogApi::debug('客户端getPayStatus ，$data返回结果', $data);
		return array_merge($result, $data);
	}
	
	/**
	 * 判断代扣->预授权
	 * @param type $param
	 */
	private static function __withholdFundAuth($param) {
		//记录最终结果
		$result = [];
		//判断是否已经签约了代扣 
		try{
			$withhold = WithholdQuery::getByUserChannel($param['userId'],$param['payChannelId']);
			$result['withholdStatus'] = false;
		}catch(\Exception $e){
			$result['withholdStatus'] = true;
		}
		//预授权金额为0
		if( $param['fundauthAmount'] == 0 ){
			$result['fundauthStatus'] = false;
		}
		//预授权金额不为0
		else{
			$result['fundauthStatus'] = true;
		}
		return $result;
	}
	/**
	 * 判断支付->预授权
	 * @param type $param
	 */
	private static function __paymentFundAuth($param) {
		//记录最终结果
		$result = [];
		//判断预授权
		//创建普通支付的支付单
		if( $param['fundauthAmount'] == 0 ){
			$result['fundauthStatus'] = false;
		}
		//创建支付+预授权的支付单
		else{
			$result['fundauthStatus'] = true;
		}
		return $result;
	}


	/**
	 * 校验订单创建过程中 支付单创建需要的参数
	 * @param Array $param
	 */
	private static function __praseParam( &$param ) {
		$paramArr = filter_array($param, [
	 		'payType' => 'required',//支付方式 【必须】<br/>
	 		'payChannelId' => 'required',//支付渠道 【必须】<br/>
			'userId' => 'required',//业务用户ID<br/>
			'fundauthAmount' => 'required',//Price 预授权金额，单位：元<br/>
		]);
		if( count($paramArr) != 4 ){
			return FALSE;
		}
		$param = $paramArr;
		return true;
	}


	public static function getOrderinfoByOrderNo($orderNo)
    {
        if (empty($orderNo)) return false;
        $orderInfo = OrderRepository::getOrderInfo(['order_no'=>$orderNo]);
        return $orderInfo;

    }




}