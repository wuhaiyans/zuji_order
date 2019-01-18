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
use App\Lib\Payment\LebaifenApi;
use App\Lib\Risk\Risk;
use App\Lib\Risk\Yajin;
use App\Lib\Warehouse\Delivery;
use App\Order\Controllers\Api\v1\ReturnController;
use App\Order\Models\OrderDelivery;
use App\Order\Models\OrderExtend;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Models\OrderInsurance;
use App\Order\Models\OrderVisit;
use App\Order\Modules\Inc;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\PublicInc;
use App\Order\Modules\Repository\Order\DeliveryDetail;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\Order\OrderScheduleOnce;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderMiniRepository;
use App\Order\Modules\Repository\OrderPayPaymentRepository;
use App\Order\Modules\Repository\OrderPayRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\OrderRiskCheckLogRepository;
use App\Order\Modules\Repository\OrderRiskRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use App\Order\Modules\Repository\Pay\Channel;
use App\Order\Modules\Repository\Pay\WithholdQuery;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use Illuminate\Support\Facades\DB;
use App\Lib\Order\OrderInfo;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use zuji\order\OrderStatus;


class OrderOperate
{
    /**
     * 订单发货接口
     * @author wuhaiyan
     * @param $orderDetail array
     * [
     *  'order_no'=>'',         //【必须】string 订单编号
     *  'logistics_id'=>''      //【必须】int 物流渠道ID
     *  'logistics_no'=>''      //【必须】string 物流单号
     *  'return_address_type'=>false,//【非必须】 类型：bool 回寄地址标识(true修改,false不修改)
     *  'return_address_value'=>'',//【非必须】 类型：String 回寄地址
     *  'return_name'=>'',//【非必须】 类型：String 回寄姓名
     *  'return_phone'=>'',//【非必须】 类型：String 回寄电话
     * ]
     * @param $goods_info       //【必须】 array 商品信息 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     * ]
     *@param $operatorInfo //【必须】 array 操作人员信息
     * [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】string 用户ID
     *      'user_name'=>1, //【必须】string 用户名
     * ]
     * @return boolean
     */

    public static function delivery($orderDetail,$goodsInfo,$operatorInfo=[]){
        $res=redisIncr("order_delivery".$orderDetail['order_no'],10);
        if($res>1){
            set_msg("操作太快，请稍等重试");
            return false;
        }
        //更新订单状态
        $order = Order::getByNo($orderDetail['order_no']);
        if(!$order){
            set_msg("获取订单信息失败");
            return false;
        }
        $orderInfo =$order->getData();
        DB::beginTransaction();
            //判断是否是订单发货
            if($orderInfo['freeze_type'] == Inc\OrderFreezeStatus::Non){
                $b = self::_orderDelvery($order,$orderDetail,$goodsInfo,$operatorInfo);
                if(!$b){
                    LogApi::alert("OrderDelivery:".$orderDetail['order_no'].get_msg(),[],[config('web.order_warning_user')]);
                    LogApi::error("OrderDelivery:".$orderDetail['order_no'].get_msg());
                    DB::rollBack();
                    return false;
                }
                //判断短租订单服务时间 判断是否是延迟发货 线下不延迟发货
                if($orderInfo['zuqi_type'] == Inc\OrderStatus::ZUQI_TYPE_DAY && $orderInfo['order_type'] != Inc\OrderStatus::orderActivityService && $orderInfo['order_type'] != Inc\OrderStatus::orderStoreService){
                    $b = self::_orderDelayDelivery($orderDetail['order_no']);
                    if(!$b){
                        LogApi::alert("OrderDelivery-delayError:".$orderDetail['order_no'].get_msg(),[],[config('web.order_warning_user')]);
                        LogApi::error("OrderDelivery-delayError:".$orderDetail['order_no'].get_msg());
                        DB::rollBack();
                        return false;
                    }
                    //插入操作日志
                    OrderLogRepository::add(1,'system',\App\Lib\PublicInc::Type_System,$orderDetail['order_no'],"延迟发货",'短租延迟发货');
                }
                //如果是线下订单 自动确认收货 - 调用确认收货操作
                if($orderInfo['order_type'] == Inc\OrderStatus::orderStoreService){
                    $params['order_no'] =$orderInfo['order_no'];
                    $userInfo = self::_orderReceive($order,$params,1);
                    if(!$userInfo){
                        LogApi::alert("OrderDelivery-orderReceive:".$orderDetail['order_no'].get_msg(),[],[config('web.order_warning_user')]);
                        LogApi::error("OrderDelivery-orderReceive:".$orderDetail['order_no'].get_msg());
                        DB::rollBack();
                        return false;
                    }

                }

                DB::commit();
                if($orderInfo['order_type'] != Inc\OrderStatus::orderStoreService){
                    //增加确认收货队列
                    $schedule = new OrderScheduleOnce(['user_id'=>$orderInfo['user_id'],'order_no'=>$orderInfo['order_no']]);
                    if($orderInfo['zuqi_type'] ==1){
                        $schedule->OrderDayReceive();
                    }else{
                        $schedule->OrderMonthReceive();
                    }

                    // 订单发货成功后 发送短信
                    $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderDetail['order_no'],SceneConfig::ORDER_DELIVERY);
                    $orderNoticeObj->notify();

                }

                //推送到区块链
                $b =OrderBlock::orderPushBlock($orderDetail['order_no'],OrderBlock::OrderShipped);
                LogApi::info("OrderDelivery-addOrderBlock:".$orderDetail['order_no']."-".$b);
                if($b==100){
                    LogApi::alert("OrderDelivery-addOrderBlock:".$orderDetail['order_no']."-".$b,[],[config('web.order_warning_user')]);
                }

                //如果线下增加自动确认收货推送到区块链
                if($orderInfo['order_type'] == Inc\OrderStatus::orderStoreService) {
                    //推送到区块链
                    $b = OrderBlock::orderPushBlock($orderDetail['order_no'], OrderBlock::OrderTakeDeliver);
                    LogApi::info("OrderDeliveryReceive-addOrderBlock:" . $orderDetail['order_no'] . "-" . $b);
                    if ($b == 100) {
                        LogApi::alert("OrderDeliveryReceive-addOrderBlock:" . $orderDetail['order_no'] . "-" . $b, [], [config('web.order_warning_user')]);
                    }
                }

                return true;

            }else {
                //判断订单冻结类型 冻结就走换货发货
                $b = OrderReturnCreater::createchange($orderDetail, $goodsInfo,$operatorInfo);
                LogApi::error("OrderDelivery-createchange:");
                if (!$b) {
                    set_msg("换货发货失败");
                    LogApi::error("OrderDelivery-createchange1:");
                    DB::rollBack();
                    return false;
                }
                DB::commit();
                return true;
            }

    }

    /**
     * 发货操作
     * @param  同 delivery 方法
     * @return bool
     */
    private static function _orderDelvery(Order $order,$orderDetail,$goodsInfo,$operatorInfo){

        $orderInfo =$order->getData();
        //更新订单表状态
        $b=$order->deliveryFinish();
        if(!$b){
            set_msg("更新订单状态失败");
            return false;
        }
        //增加订单发货信息
        $b =DeliveryDetail::addOrderDelivery($orderDetail);
        if(!$b){
            set_msg("增加订单发货信息");
            return false;
        }
        //增加发货详情
        $b =DeliveryDetail::addGoodsDeliveryDetail($orderDetail['order_no'],$goodsInfo);
        if(!$b){
            set_msg("增加发货详情失败");
            return false;
        }
        //如果回寄地址发生改变 需要更新地址
        $returnType = $orderDetail['return_address_type'] ?? false;
        if($orderDetail['return_address_type']){
            $GoodsExtend =  OrderGoodsExtend::where('order_no', '=', $orderDetail['order_no'])->first();
            if($GoodsExtend){
                $orderGoodsExtend = $GoodsExtend->toArray();
                $GoodsExtend->return_address_value =$orderDetail['return_address_value']?? $orderGoodsExtend['return_address_value'];
                $GoodsExtend->return_name =$orderDetail['return_name']?? $orderGoodsExtend['return_name'];
                $GoodsExtend->return_phone =$orderDetail['return_phone']?? $orderGoodsExtend['return_phone'];
                $GoodsExtend->update_time =time();
                $b = $GoodsExtend->save();
                if(!$b){
                    set_msg("修改还机回寄地址失败");
                    return false;
                }
            }
        }
//        //增加发货时生成合同 -- 走队列
//        $b = DeliveryDetail::addDeliveryContract($orderDetail['order_no'],$goodsInfo);
//        if(!$b) {
//            set_msg("生成合同失败");
//            return false;
//        }

        //增加发货时生成合同队列
        //发送订单消息队列
        $schedule = new OrderScheduleOnce(['user_id'=>$orderInfo['user_id'],'order_no'=>$orderInfo['order_no']]);
        //生成合同
        $schedule->DeliveryContract();

        //增加操作日志
        if(!empty($operatorInfo)){
            OrderLogRepository::add($operatorInfo['user_id'],$operatorInfo['user_name'],$operatorInfo['type'],$orderDetail['order_no'],"发货",$orderDetail['logistics_note']);
        }
        return true;


    }

    /**
     * 订单申请发货
     * @param $orderNo 订单编号
     * @param $userId  用户ID
     * @return bool
     */
    public static function DeliveryApply($orderNo,$userId){

        //调用确认订单接口
        $data=[
            'order_no'  => $orderNo, //【必须】string 订单编号
            'remark'=>'线下订单自动待发货',      //【必须】string 备注
            'userinfo'=>[
                'uid'=>1,
                'username'=>'system',
                'type'=>\App\Lib\PublicInc::Type_System,
            ],
        ];
        $b = OrderOperate::confirmOrder($data);
        if(!$b){
            LogApi::alert("InnerService-DeliveryApply-:".$orderNo,$data,[config('web.order_warning_user')]);
            LogApi::error("InnerService-DeliveryApply-:".$orderNo,$data);
            return  ApiStatus::CODE_60001;
        }
        LogApi::info("InnerService-DeliveryApply-success:".$orderNo);
        return  ApiStatus::CODE_0;
    }

    /**
     * 发货生成合同
     * @param $orderNo 订单编号
     * @param $userId  用户ID
     * @return bool
     */
    public static function DeliveryContract($orderNo,$userId){
        LogApi::info("InnerService-DeliveryContract:".$orderNo);
        $b = DeliveryDetail::addDeliveryContract($orderNo);
        if(!$b) {
            LogApi::alert("InnerService-DeliveryContract:".$orderNo,[],[config('web.order_warning_user')]);
            LogApi::info("InnerService-DeliveryContract-error:".$orderNo);
            return  ApiStatus::CODE_60001;
        }
        LogApi::info("InnerService-DeliveryContract-success:".$orderNo);
        return  ApiStatus::CODE_0;
    }
    /**
     * 延迟发货操作
     * @param $orderNo 订单编号
     * @return  bool
     */
    private static function _orderDelayDelivery($orderNo){
//判断发货三天后的起租时间 是否 大于 起租时间
        $beginTime = strtotime(date("Y-m-d",time()+86400*3));
        $goodsData = OrderGoodsRepository::getGoodsByOrderNo($orderNo);
        $goodsData =objectToArray($goodsData);
        foreach ($goodsData as $k=>$v){
            if($v['begin_time'] < $beginTime){
                //延期天数
                $delayDay = ($beginTime -$v['begin_time'])/86400;
                $endTime =$v['end_time']+$delayDay*86400;
                //如果起租时间小于三天后的时间  更新商品表起止时间
                $goods = \App\Order\Modules\Repository\Order\Goods::getByGoodsId($v['id']);
                $b =$goods->updateGoodsServiceTime([
                    'begin_time'=>$beginTime,
                    'end_time'=>$endTime,
                ]);
                if(!$b){
                    set_msg("修改商品服务时间失败");
                    return false;
                }
                //修改 服务周期表时间
                $b =ServicePeriod::updateUnitTime($v['goods_no'],$beginTime,$endTime);

                if(!$b){
                    set_msg("修改短租服务时间失败");
                    return false;
                }


                //修改订单分期扣款时间
                $b = OrderGoodsInstalmentRepository::delayInstalment($orderNo,$delayDay);
                if(!$b){
                    set_msg("修改分期延期扣款时间失败");
                    return false;

                }
            }
        }
        return true;
    }

    /**
     * 获取订单状态流
     * @author wuhaiyan
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
        if($orderInfo['order_status'] == Inc\OrderStatus::OrderAbnormal){
            $res[] =['time' =>$orderInfo['update_time'],'status'=>"异常关闭"];
            return $res;
        }

        return $res;
    }

    /**
     *  增加订单出险/取消出险记录
     * @author wuhaiyan
     * @param $params
     * [
     *          'order_no'=>'',     //【必须】string 订单编号
     *          'goods_no'=>'',     //【必须】string 商品编号
     *          'remark'=>'',       //【必须】string 备注信息
     *          'type'=>'',         //【必须】int 类型 1出险 2取消出险
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
    public static function getInsuranceInfo($params, $column='*'){

        $whereArray[] = ['order_no', '=', $params['order_no']];
        if (isset($params['goods_no'])) {
            $whereArray[] = ['goods_no', '=', $params['goods_no']];
        }


        $insuranceData =  OrderInsurance::where($whereArray)->select($column)->get();
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
     * @author wuhaiyan
     * @param $params
     * [
     *          'order_no'=>'',     //【必须】string 订单编号
     *          'visit_id'=>'',     //【必须】int 联系备注ID
     *          'visit_text'=>'',   //【必须】string 备注信息
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
     * 保存回访标识
     * @author wuhaiyan
     * @param $params
     * [
     *          'order_no'=>'',     //【必须】string 订单编号
     *          'check_status'=>'', //【必须】int    状态ID     3，复核通过； 4，复核拒绝
     *          'check_text'=>'',   //【必须】string 备注信息
     * ]
     * @param $userinfo
     * [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'uid'=>1,   //【必须】string 用户ID
     *      'username'=>1, //【必须】string 用户名
     *      'ip'=>1,    //【必须】string IP 地址
     * ]
     * @return array|bool
     */

    public static function saveOrderRiskCheck($params,$userInfo)
    {
        DB::beginTransaction();
        try{
            //查询订单信息
            $order = Order::getByNo($params['order_no']);
            if(!$order){
                set_msg("订单不存在");
                DB::rollBack();
                return false;
            }

            $orderInfo =$order->getData();

            //更新订单风控审核状态
            $b= $order->editOrderRiskStatus($params['check_status']);
            if(!$b){
                set_msg("操作失败");
                DB::rollBack();
                return false;
            }
            //增加风控审核操作日志
            $b =OrderRiskCheckLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$params['order_no'],$params['check_text'],$params['check_status'],$orderInfo['risk_check']);
            if(!$b){
                set_msg("操作失败");
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
     * @author wuhaiyan
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
     * 获取订单风控审核日志接口
     * @author wuhaiyan
     * @param $orderNo
     * @return array|bool
     */

    public static function orderRiskCheckLog($orderNo)
    {
        if(empty($orderNo)){return false;}
        $logData = OrderRiskCheckLogRepository::getOrderLog($orderNo);
        if(!$logData){
            return [];
        }
        foreach ($logData as $k=>$v){

            $logData[$k]['operator_type_name'] = \App\Lib\PublicInc::getRoleName($v['operator_type']);
            $oldStatus='';
            if($v['old_status']){
                $oldStatus = "由“".Inc\OrderRiskCheckStatus::getStatusName($v['old_status'])."”";
            }
            $newStatus =Inc\OrderRiskCheckStatus::getStatusName($v['new_status']);
            $logData[$k]['title'] = "将风控审核状态".$oldStatus."修改为“".$newStatus."”。";
        }
        return $logData;
    }

    /**
     * 确认收货接口
     * @author wuhaiyan
     * @param  $system //【可选】int 0 前后端操作,1 系统执行任务
     * @param $params
     * [
     *  'order_no' =>'',//【必须】string 订单编号
     *  'remark'=>'',   //【必须】string 备注
     * ],
     * $userinfo [
     *      'type'=>'',     //【必须】int 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】int 用户ID
     *      'user_name'=>1, //【必须】string 用户名
     *      'mobile'=>1,    //【必须】string 手机号
     * ]
     * @return boolean
     */

    public static function deliveryReceive($params,$system=0){

        $orderNo =$params['order_no'];
        $remark = isset($params['remark'])?$params['remark']:'';

        DB::beginTransaction();
            //获取订单信息
            $order = Order::getByNo($orderNo);
            if(!$order){
                LogApi::alert("DeliveryReceive:获取订单信息失败",$params,[config('web.order_warning_user')]);
                DB::rollBack();
                return false;
            }

            $orderInfo =$order->getData();
            if($orderInfo['order_status'] != Inc\OrderStatus::OrderDeliveryed){
                LogApi::error(config('app.env')."环境 DeliveryReceive:订单状态已更改",$orderInfo);
                DB::rollBack();
                return true;
            }

            //订单确认收货系列操作
            $userInfo =self::_orderReceive($order,$params,$system);
            if(!$userInfo){
                LogApi::alert("DeliveryReceive:".get_msg(),$params,[config('web.order_warning_user')]);
                LogApi::error("DeliveryReceive:".get_msg(),$params);
                DB::rollBack();
                return false;
            }
            $params=[
                'order_no'=>$orderNo,//
                'receive_type'=>$userInfo['type'],//类型：String  必有字段  备注：签收类型1管理员，2用户,3系统，4线下
                'user_id'=>$userInfo['uid'],//
                'user_name'=>$userInfo['username'],//
            ];
            //通知给收发货系统
            $b =Delivery::orderReceive($params);
            if(!$b){
                LogApi::alert("DeliveryReceive:通知发货系统确认收货失败",$params,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 DeliveryReceive:通知发货系统确认收货失败",$params);
                DB::rollBack();
                return false;
            }
            DB::commit();
            //取消任务队列
            $cancel = JobQueueApi::cancel(config('app.env')."DeliveryReceive".$orderNo);

            //推送到区块链
            $b =OrderBlock::orderPushBlock($orderNo,OrderBlock::OrderTakeDeliver);
            LogApi::info("OrderDeliveryReceive-addOrderBlock:".$orderNo."-".$b);
            if($b==100){
                LogApi::alert("OrderDeliveryReceive-addOrderBlock:".$orderNo."-".$b,[],[config('web.order_warning_user')]);
            }

            return true;

    }

    /**
     * 订单确认收货操作
     * @param $order 订单对象
     * @param $params 参数同确认收货接口
     * @param int $system
     * @return array|false 返回操作人信息
     */

    private static function _orderReceive(Order $order,$params,$system=0){
        $orderNo =$params['order_no'];
        if($system){
            $params['remark']="系统确认收货";
            $params['userinfo']['uid'] =1;
            $params['userinfo']['username'] ="系统";
            $params['userinfo']['type'] =\App\Lib\PublicInc::Type_System;
        }
        $remark = isset($params['remark'])?$params['remark']:'';
        $userInfo =$params['userinfo']??[];
        if(empty($userInfo)){
            set_msg("操作人信息错误");
            return false;
        }
        $orderInfo = $order->getData();
        //更新订单状态
        $b =$order->sign();
        if(!$b){
            set_msg("更新订单状态失败");
            return false;
        }
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
                set_msg("更新商品服务时间失败");
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
                set_msg("增加商品租期表失败");
                return false;
            }
        }

        //更新订单商品的状态
        $b = OrderGoodsRepository::setGoodsInService($orderNo);
        if(!$b){
            set_msg("更新商品状态失败");
            return false;
        }

        //调用乐百分确认收货
        if($orderInfo['pay_type'] == PayInc::LebaifenPay){
            $b =self::lebaifenDelivery($orderNo,$orderInfo['pay_type']);
            if(!$b){
                set_msg("乐百分调用乐百分失败");
                return false;
            }
        }

        //签收后发送短信
        if($orderInfo['zuqi_type'] ==1){
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_DAY_RECEIVE);
        }else{
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_MONTH_RECEIVE);
        }
        $orderNoticeObj->notify();

        //插入操作日志
        OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$orderNo,"确认收货",$remark);

        return $userInfo;
    }

    /**
     * 服务周期计算
     * @param $beginTime
     * @param $zuqi
     * @return int
     */
    private static function calculateEndTime($beginTime, $zuqi){
        $day = Inc\publicInc::calculateDay($zuqi);
        $endTime = $beginTime + $day*86400;
        return $endTime;
    }
    /**
     * 乐百分支付 --- 确认收货调用接口
     * @author wuhaiyan
     * @params $orderNo 【必须】string 订单编号
     * @params $payType 【必须】int 支付方式
     * @return bool
     */
    public static function lebaifenDelivery($orderNo,$payType){

        if($payType == PayInc::LebaifenPay){
            //查询支付单信息
            $payInfo = OrderPayRepository::find($orderNo);
            if(empty($payInfo)){
                LogApi::alert("DeliveryReceive:乐百分支付order_pay表为空",['order_no'=>$orderNo],[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 DeliveryReceive:乐百分支付order_pay表为空",['order_no'=>$orderNo]);
                return false;
            }
            $paymentInfo = OrderPayPaymentRepository::find($payInfo['payment_no']);
            if(empty($paymentInfo)){
                LogApi::alert("DeliveryReceive:乐百分支付order_pay_payment表为空",$payInfo,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 DeliveryReceive:乐百分支付order_pay_payment表为空",$payInfo);
                return false;
            }

            try{
                //调用乐百分确认收货接口
                $param =[
                        'payment_no'		=> $paymentInfo['out_payment_no'],// 业务系统 支付交易码
                        'out_payment_no'	=> $payInfo['payment_no'],// 支付系统 支付交易码
                ];
                $res =LebaifenApi::confirmReceipt($param);
                return true;

            }catch (\Exception $e){
                LogApi::alert("DeliveryReceive:乐百分支付调用乐百分接口失败",$param,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 确认收货乐百分支付 确认收货调用乐百分接口失败",array_merge($payInfo,$paymentInfo));
                return false;
            }
            return true;


        }
        return true;
    }
    /**
     *  获取乐百分 支付分期结果
     * @author wuhaiyan
     * @params $orderNo 【必须】string 订单编号
     * @return bool|array
     */
    public static function getLebaifenInstalment($orderNo){

        //获取订单信息
        $order = Order::getByNo($orderNo);
        if(!$order){
            return false;
        }
        $orderInfo = $order->getData();
        $payType =$orderInfo['pay_type'];//获取支付方式
        $orderNo =$orderInfo['order_no'];

        $rentAmount =normalizeNum($orderInfo['order_amount']+$orderInfo['order_insurance']);
        $totalAmount =normalizeNum($rentAmount+$orderInfo['order_yajin']);
        $txnTerms =15;
        $instalmentInfo =[
            'txn_amount'	=> $totalAmount,	// 总金额；单位：分
            'txn_terms'		=> $txnTerms,	// 总分期数
            'rent_amount'	=> $rentAmount,	// 总租金；单位：分
            'month_amount'	=> normalizeNum(substr(sprintf("%.3f",$orderInfo['order_amount']/15),0,-1)),	// 每月租金；单位：分
            'remainder_amount' => normalizeNum($orderInfo['order_amount']*100%$txnTerms/100),	// 每月租金取整后,总租金余数；单位：分
            'sum_amount'	=> 0.00,	// 已还总金额；单位：分
            'sum_terms'		=> 0,	// 已还总期数
            'remain_amount' =>  $rentAmount,	// 剩余总金额；单位：分
            'first_other_amount'=>normalizeNum($orderInfo['order_insurance']),// 首期额外金额；单位：分 碎屏险
            'no_return_zujin' =>$rentAmount,//未还租金
        ];
        if($payType == PayInc::LebaifenPay){
            //查询支付单信息
            $payInfo = OrderPayRepository::find($orderNo);
            $paymentInfo = OrderPayPaymentRepository::find($payInfo['payment_no']);
            if(empty($paymentInfo)){
                return $instalmentInfo;
            }

            try{
                //调用乐百分分期信息接口
                $param =[
                    'payment_no'		=> $paymentInfo['out_payment_no'],// 业务系统 支付交易码
                    'out_payment_no'	=> $payInfo['payment_no'],// 支付系统 支付交易码
                ];
                $res =LebaifenApi::getPaymentInfo($param);
                $sum_amount = normalizeNum($res['sum_amount']/100);
                if( $sum_amount >= $rentAmount ){
                    //已还租金
                    $sum_amount = $rentAmount;
                    //已还期数
                    $sum_terms = 15;
                    //未还租金
                    $no_return_zujin = 0.00;
                }else{
                    $sum_terms = $res['sum_terms'];
                    //未还租金
                    $no_return_zujin = normalizeNum($orderInfo['order_amount']+$orderInfo['order_insurance']-$res['sum_amount']);
                }
                $instalmentInfo =[
	 		        'payment_no'	=> $res['payment_no'],	// 支付系统 支付交易码
	 		        'out_payment_no'=> $res['out_payment_no'],	// 业务系统 支付交易码
	 		        'status'		=> $res['status'],	// 状态；0：未支付；1：已支付；2：已结束
	 		        'txn_amount'	=> normalizeNum($res['txn_amount']/100),	// 总金额；单位：分
	 		        'txn_terms'		=> $res['txn_terms'],	// 总分期数
	 		        'rent_amount'	=> normalizeNum($res['rent_amount']/100),	// 总租金；单位：分
	 		        'month_amount'	=> normalizeNum($res['month_amount']/100),	// 每月租金；单位：分
	 		        'remainder_amount' => normalizeNum($res['remainder_amount']/100),	// 每月租金取整后,总租金余数；单位：分
	 		        'sum_amount'	=> $sum_amount,	// 已还总金额；单位：分
	 		        'sum_terms'		=> $sum_terms,	// 已还总期数；
                    'remain_amount' => normalizeNum($res['remain_amount']/100),	// 剩余还款总金额（包含租金押金）；单位：分
                    'first_other_amount'=>normalizeNum($res['first_other_amount']/100),// 首期额外金额；单位：分 碎屏险
                    'no_return_zujin' =>$no_return_zujin,//未还租金
                ];
                return $instalmentInfo;

            }catch (\Exception $e){
                LogApi::alert("getLebaifenInstalment:获取乐百分分期 信息 接口失败",$param,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 获取乐百分分期 信息 接口失败",$e->getMessage());
                return false;
            }

        }
        return [];
    }
    /**
     * 订单统计查询{"item":{"name":"first_other_amount","must":1,"type":0,"remark":"","mock":"99.00","drag":1,"show":0},"index":8}
     * @author wuhaiyan
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
     * @author wuhaiyan
     * @param
     * $data[
     *   'order_no'  => '', //【必须】string 订单编号
     *   'remark'=>'',      //【必须】string 备注
     *   'userinfo '=>[
     *      'type'=>'',     //【必须】int 用户类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,   //【必须】int用户ID
     *      'user_name'=>1, //【必须】string用户名
     *      'mobile'=>1,    //【必须】string手机号
     *      ]
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
                LogApi::alert("OrderConfirm-updateOrderStatus:".$data['order_no'],$data,[config('web.order_warning_user')]);
                LogApi::error("OrderConfirm-updateOrderStatus:".$data['order_no'],$data);
                DB::rollBack();
                return false;
            }

            $goodsInfo = OrderRepository::getGoodsListByOrderId($data['order_no']);
            $orderInfo = OrderRepository::getOrderInfo(['order_no'=>$data['order_no']]);
            $orderInfo['business_key'] = Inc\OrderStatus::BUSINESS_ZUJI;
            $orderInfo['business_no'] =$data['order_no'];
            $orderInfo['order_no']=$data['order_no'];

            //通知收发货系统 -申请发货
            $delivery =Delivery::apply($orderInfo,$goodsInfo);
            if(!$delivery){
                LogApi::alert("OrderConfirm-DeliveryApply:".$data['order_no'],$orderInfo,[config('web.order_warning_user')]);
                LogApi::error("OrderConfirm-DeliveryApply:".$data['order_no'],$orderInfo);
                DB::rollBack();
                return false;
            }

            $userInfo =$data['userinfo'];
            OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$data['order_no'],"申请发货",$data['remark']);
            //推送到区块链
            $b =OrderBlock::orderPushBlock($data['order_no'],OrderBlock::OrderConfirmed);
            LogApi::info("OrderConfirm-addOrderBlock:".$data['order_no']."-".$b);
            if($b==100){
                LogApi::alert("OrderConfirm-addOrderBlock:".$data['order_no']."-".$b,[],[config('web.order_warning_user')]);
            }
            DB::commit();
            return true;

        }catch (\Exception $exc){
            DB::rollBack();
            LogApi::error("OrderConfirm-Exception:".$data['order_no'].$exc->getMessage());
            return false;
        }

    }
    /**
     * 保存订单风控信息
     * @author wuhaiyan
     * @param $orderNo 订单编号
     * @param string $userId 用户id
     * @return bool|string
     */
    public static function orderRiskSave($orderNo,$userId)
    {
        //查询订单信息
        $order = $order = Order::getByNo($orderNo);
        if(!$order){
            LogApi::error(config('app.env')."[orderRiskSave] Order-non-existent:".$orderNo);
            return ApiStatus::CODE_31006;
        }
        $orderInfo = $order->getData();

        //查询商品信息
        $marketPrice = 0;

        $goodsInfo = OrderGoodsRepository::getGoodsByOrderNo($orderNo);
        if($goodsInfo){
            $goodsInfo =objectToArray($goodsInfo);
            foreach ($goodsInfo as $k=>$v){
                $marketPrice +=$v['market_price'];
            }
        }
        //市场价与 订单押金的差额
        $cha = $marketPrice-$orderInfo['order_yajin'];
        $amount = $cha >0?$cha:0;

        //获取风控信息信息
        try{
            $knight =Risk::getAllKnight(['user_id'=>$userId,'amount'=>$amount*100]);
            LogApi::info(config('app.env')."[orderRiskSave] GetAllKnight-info:".$userId,['user_id'=>$userId,'amount'=>$amount*100]);
        }catch (\Exception $e){
            LogApi::error(config('app.env')."[orderRiskSave] GetAllKnight-error:".$userId,['user_id'=>$userId,'amount'=>$amount*100]);
            return  ApiStatus::CODE_31006;
        }


        $riskStatus = Inc\OrderRiskCheckStatus::SystemPass;

        if($orderInfo['order_type'] == Inc\OrderStatus::orderMiniService && $knight['risk_grade'] == 'REJECT'){
                $riskStatus = Inc\OrderRiskCheckStatus::ProposeReview;
        }
        $b = $order->editOrderRiskStatus($riskStatus);
        if(!$b){
            LogApi::error(config('app.env')."[orderRiskSave] Order-editOrderRiskStatus:".$orderNo);
            return ApiStatus::CODE_31006;
        }

        //保存风控审核日志
        $b =OrderRiskCheckLogRepository::add(0,"系统",\App\Lib\PublicInc::Type_System,$orderNo,"系统风控操作",$riskStatus);
        if(!$b){

            LogApi::error(config('app.env')."[orderRiskSave] save-orderRiskCheckLogErro:".$orderNo);
            return ApiStatus::CODE_31006;
        }

        //获取风控信息详情 保存到数据表

        $riskDetail =$knight['risk_detail']?? true;
        if (is_array($riskDetail) && !empty($riskDetail)) {
            foreach ($riskDetail as $k=>$v){
                $riskData =[
                    'order_no'=>$orderNo,  // 订单编号
                    'data' => json_encode($riskDetail[$k]),
                    'type'=>$k,
                ];
                $id =OrderRiskRepository::add($riskData);
                if(!$id){

                    LogApi::error(config('app.env')."[orderRiskSave] save-error",$riskData);
                    return  ApiStatus::CODE_31006;
                }
            }
            LogApi::info(config('app.env')."[orderRiskSave]save-success：",$riskData);
            return  ApiStatus::CODE_0;
        }


    }

    /**
     * 发送订单押金信息返回风控系统
     * @author wuhaiyan
     * @param $orderNo 订单编号
     * @param string $userId 用户id
     * @return bool|string
     */
    public static function YajinReduce($orderNo,$userId)
    {

        //查询订单信息
        $order = $order = Order::getByNo($orderNo);
        if(!$order){
            LogApi::error(config('app.env')."[orderYajinReduce] Order-non-existent:".$orderNo);
            return ApiStatus::CODE_31006;
        }
        $orderInfo = $order->getData();

        $jianmian = ($orderInfo['goods_yajin']-$orderInfo['order_yajin'])*100;
        //请求押金接口
        try{
            $yajin = Yajin::MianyajinReduce(['user_id'=>$userId,'jianmian'=>$jianmian,'order_no'=>$orderNo,'appid'=>$orderInfo['appid']]);
        }catch (\Exception $e){
            LogApi::error(config('app.env')."[orderYajinReduce] Yajin-interface-error-".$orderNo.":".$e->getMessage());
            return  ApiStatus::CODE_31006;
        }

        return  ApiStatus::CODE_0;
    }

    /**
     * 发送订单押金信息返回风控系统
     * @author wuhaiyan
     * @param $orderNo 订单编号
     * @param string $userId 用户id
     * @param string $orderStatus 订单完结后状态
     * @return bool
     */
    public static function YajinRecovery($orderNo,$userId,$orderStatus)
    {
        //订单状态信息 - 取消状态
        if($orderStatus ==  Inc\OrderStatus::OrderCancel || $orderStatus == Inc\OrderStatus::OrderClosedRefunded){
            $type =2;
        }elseif($orderStatus == Inc\OrderStatus::OrderCompleted){
            $type =1;
        }else{
            LogApi::error(config('app.env')."[orderYajinRecovery] OrderStatus-Error-".$orderNo);
            return false;
        }
        //请求押金接口
        try{
            $yajin = Yajin::OrderComplete(['user_id'=>$userId,'type'=>$type,'order_no'=>$orderNo]);
        }catch (\Exception $e){
            LogApi::error(config('app.env')."[orderYajinRecovery] Yajin-interface-error-".$orderNo.":".$e->getMessage());
            return false;
        }

        return true;
    }
    /**
     * 取消订单
     * Author: heaven
     * @param $orderNo 订单编号
     * @param string $userInfo 数组
     * @return bool|string
     */
    public static function cancelOrder($orderNo,$userInfo='',$reasonId = '', $resonText='')
    {
        if (empty($orderNo)) {
            return  ApiStatus::CODE_31001;
        }
        $userId = $userInfo['uid'];
        //增加操作日志

        $resonInfo = '';

        if ($reasonId) {

            $resonInfo = Inc\OrderStatus::getOrderCancelResasonName($reasonId);
        }

        if ($resonText) {

            $resonInfo = $resonText;

        }
        //查询订单的状态
        $orderInfoData =  OrderRepository::getInfoById($orderNo,$userId);

        //如果订单为已支付 取消订单走申请退款方法
        if($orderInfoData['order_status'] == Inc\OrderStatus::OrderPayed && $userInfo['username'] !='system'){
            $params=[
                'order_no'=>$orderNo,
                'user_id'=>$userId,
                'reason_text'=>$resonInfo,
            ];
            $b = OrderReturnCreater::createRefund($params,$userInfo);
            if(!$b){
                return ApiStatus::CODE_31010;
            }
            return ApiStatus::CODE_0;
        }

        if ($orderInfoData['order_status']!=Inc\OrderStatus::OrderWaitPaying)  return  ApiStatus::CODE_31007;
        //开启事物
        DB::beginTransaction();
        try {

            //关闭订单状态
            $orderData =  OrderRepository::closeOrder($orderNo,$userId);
            if (!$orderData) {
                LogApi::alert("CancelOrder:关闭订单状态失败",['order_no'=>$orderNo],[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 CancelOrder:关闭订单状态失败",['order_no'=>$orderNo]);
                DB::rollBack();
                return ApiStatus::CODE_31002;
            }


            //分期关闭
            //查询分期
            $isInstalment   =   OrderGoodsInstalmentRepository::queryCount(['order_no'=>$orderNo]);
            if ($isInstalment) {
                $success =  Instalment::close(['order_no'=>$orderNo]);
                if (!$success) {
                    LogApi::alert("CancelOrder:关闭分期失败",['order_no'=>$orderNo],[config('web.order_warning_user')]);
                    LogApi::error(config('app.env')."环境 CancelOrder:关闭分期失败",['order_no'=>$orderNo]);
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
                LogApi::alert("CancelOrder:恢复库存失败",$goods_arr,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 CancelOrder:恢复库存失败",$goods_arr);
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
                        LogApi::alert("CancelOrder:解除代扣失败",$params,[config('web.order_warning_user')]);
                        LogApi::error(config('app.env')."环境 CancelOrder:解除代扣失败",$params);
                        DB::rollBack();
                        return ApiStatus::CODE_31008;
                    }

                }catch (\Exception $e){
                    //未签约 不解除
                }

            }

            //返回风控押金信息
            $b =self::YajinRecovery($orderNo,$userId,Inc\OrderStatus::OrderCancel);
            if(!$b){
                LogApi::alert("CancelOrder:返回风控押金信息",['order_no'=>$orderNo],[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."环境 CancelOrder:返回风控押金信息",['order_no'=>$orderNo]);
                DB::rollBack();
                return ApiStatus::CODE_31009;
            }

            //优惠券归还
            //通过订单号获取优惠券信息
            $orderCouponData = OrderRepository::getCouponListByOrderId($orderNo);

            if ($orderCouponData) {
                $coupon_id = array_column($orderCouponData, 'coupon_id');
                $success =  Coupon::setCoupon(['user_id'=>$userId ,'coupon_id'=>$coupon_id],$orderInfoData['appid']);

                if ($success) {
                    LogApi::alert("CancelOrder:恢复优惠券失败",$orderCouponData,[config('web.order_warning_user')]);
                    LogApi::error(config('app.env')."环境 CancelOrder:恢复优惠券失败",$orderCouponData);
                    DB::rollBack();
                    return ApiStatus::CODE_35023;
                }

            }


            DB::commit();
            //取消定时任务
            $cancel = JobQueueApi::cancel(config('app.env')."OrderCancel_".$orderNo);
            // 订单取消后发送取消短息。;
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_CANCEL);
            $orderNoticeObj->notify();




            OrderLogRepository::add($userId ,$userInfo['username'],\App\Lib\PublicInc::Type_User,$orderNo,$resonInfo."取消","用户未支付取消");

            return ApiStatus::CODE_0;

        } catch (\Exception $exc) {
            DB::rollBack();
            LogApi::alert("CancelOrder:未支付取消订单异常",['error'=>$exc->getMessage()],[config('web.order_warning_user')]);
            LogApi::info("未支付取消订单异常",$exc);
            return  ApiStatus::CODE_31006;
        }

    }
    /**
     * 获取风控和认证信息
     * @author wuhaiyan
     * @param $orderNo
     * @return array
     */
    public static function getOrderRisk($orderNo){
        $riskArray =[];
        //获取风控系统信息
        $orderRisk =OrderRiskRepository::getRisknfoByOrderNo($orderNo);

        if($orderRisk){
            foreach ($orderRisk as $k=>$v){
                if($v['data']==""){
                    return self::getOrderRiskV1($orderNo);
                }
                $riskArray[$v['type']]=json_decode($v['data']);
            }
            $arr['risk_info'] =$riskArray;
            return $arr;
        }

        return self::getOrderRiskV1($orderNo);

    }


    /**
     * 获取风控芝麻信息
     * @author heaven
     * @param $orderNo
     * @return array
     */
    public static function getOrderRiskScore($orderNo, $type='zhima'){

        $zhimaScoreKeys = 'zhima_score_'.$orderNo;
        if (Redis::EXISTS($zhimaScoreKeys))
        {
            return Redis::get($zhimaScoreKeys);
        }
        $riskScore = '';
        //获取风控系统信息
        $orderRisk =OrderRiskRepository::getRisknfoByOrderNo($orderNo, $type);

        if($orderRisk){
            $orderRisk = json_decode($orderRisk[0]['data'],true);

            $riskScore =  $orderRisk['score'] ?? '';

        }
        Redis::set($zhimaScoreKeys, $riskScore);
        return $riskScore;

    }




    /**
     * 获取风控和认证信息
     * @author wuhaiyan
     * @param $orderNo
     * @return array
     */
    public static function getOrderRiskV1($orderNo){

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
            //组装数据进行返回
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
     * @author wuhaiyan
     * @param $orderNo 订单编号
     * @return boolean
     */
    public static function isOrderComplete($orderNo){
        //查询订单商品信息
        $goods = OrderGoodsRepository::getGoodsByOrderNo($orderNo);
        LogApi::info("order查询订单商品信息",$goods);
        if(!$goods){
            return false;
        }
        $goods = objectToArray($goods);
        $orderStatus =0;
        foreach ($goods as $k=>$v){
            //判断订单状态是否是 退款/退货 如果是 状态为 退款关闭
            if($v['goods_status'] == Inc\OrderGoodStatus::REFUNDED || $v['goods_status'] == Inc\OrderGoodStatus::EXCHANGE_REFUND){
                $orderStatus = Inc\OrderStatus::OrderClosedRefunded;
            }
            //判断商品状态是 否是完成状态 买断/还机
            if($v['goods_status'] == Inc\OrderGoodStatus::COMPLETE_THE_MACHINE || $v['goods_status'] == Inc\OrderGoodStatus::CLOSED_THE_MACHINE || $v['goods_status'] == Inc\OrderGoodStatus::BUY_OUT){
                $orderStatus = Inc\OrderStatus::OrderCompleted;
            }
            //查询是否有 未还机 未退款 未买断 订单就是未结束的 就返回
            if($v['goods_status']!=Inc\OrderGoodStatus::REFUNDED && $v['goods_status']!=Inc\OrderGoodStatus::COMPLETE_THE_MACHINE && $v['goods_status']!=Inc\OrderGoodStatus::CLOSED_THE_MACHINE && $v['goods_status']!=Inc\OrderGoodStatus::BUY_OUT && $v['goods_status'] != Inc\OrderGoodStatus::EXCHANGE_REFUND){
            //var_dump("订单未完成");die;
                return true;
            }
        }

        if($orderStatus!=0){
            //如果订单完成 更新订单状态
            $order = Order::getByNo($orderNo);
            LogApi::info("order查询订单信息",$order);
            $b =$order->updateStatus($orderStatus,0);
            if(!$b){
                return false;
            }
            $orderInfo =$order->getData();
            LogApi::info("order查询订单信息转成数组",$orderInfo);
            //解除代扣的订单绑定
            $b =self::orderUnblind($orderInfo);
            LogApi::info("order解除代扣的订单绑定",$b);
            if(!$b){
                return false;
            }

            //返回风控押金信息
            $b =self::YajinRecovery($orderNo,$orderInfo['user_id'],$orderStatus);
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
     * @author wuhaiyan
     * @param $orderInfo 订单信息
     * @return bool
     */

    public static function orderUnblind($orderInfo){
        LogApi::info("order获取订单信息",$orderInfo);
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
                LogApi::info("order解除代扣",$orderInfo);
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

        //订单风控审核状态名称
        $orderData['risk_check_name'] = Inc\OrderRiskCheckStatus::getStatusName($orderData['risk_check']);

        //支付方式名称
        $orderData['pay_type_name'] = Inc\PayInc::getPayName($orderData['pay_type']);

        //应用来源
        $orderData['appid_name'] = OrderInfo::getAppidInfo($orderData['appid']);
        $orderData['zm_order_no']    =  '';
        //获取小程序芝麻单号
        if ($orderData['order_type']==Inc\OrderStatus::orderMiniService) {

            $miniOrderData = OrderMiniRepository::getMiniOrderInfo($orderNo);

            if ($miniOrderData) {

                $orderData['zm_order_no']    =    $miniOrderData['zm_order_no']?? '';


            }


        }


        //订单金额
        $orderData['order_gooods_amount']  = normalizeNum($orderData['order_amount']+$orderData['coupon_amount']+$orderData['discount_amount']+$orderData['order_insurance']);
        //支付金额
        $orderData['pay_amount']  = normalizeNum($orderData['order_amount']+$orderData['order_insurance']);
        //总租金
        $orderData['zujin_amount']  =   $orderData['order_amount'];
        //碎屏意外险
        $orderData['order_insurance_amount']  =   $orderData['order_insurance'];
        //授权总金额
        $orderData['yajin_amount']  =   $orderData['order_yajin'];

        $orderData['certified_platform_name']  =   Certification::getPlatformName($orderData['certified_platform']);
        //判断是否第三平台下过单
        $orderData['matching_name']  = $orderData['matching']? '是':'否';

        $order['order_info'] = $orderData;

        //订单商品列表相关的数据
        $actArray = Inc\OrderOperateInc::orderInc($orderData['order_status'], 'actState');

        $goodsData =  self::getGoodsListActState($orderNo, $actArray, $goodsExtendArray,$orderData['pay_type']);

        if (empty($goodsData)) return apiResponseArray(ApiStatus::CODE_32002,[]);

        $order['goods_info'] = $goodsData;
        //设备扩展信息表
        $goodsExtendData =  self::getOrderDeliveryInfo($orderNo);
        if ($goodsExtendData) {
            $logisticsArray =   config('web.logistics');
            $goodsExtendData['logistics_name'] = isset($logisticsArray[$goodsExtendData['logistics_id']])   ?  $logisticsArray[$goodsExtendData['logistics_id']]:   '';
        }
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

        $newParam['appid']=  $param['appid'];

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


                $goodsData =  self::getGoodsListActState($values['order_no'], $actArray, array(), $values['pay_type']);

                $orderListArray['data'][$keys]['goodsInfo'] = $goodsData;

//                $orderListArray['data'][$keys]['admin_Act_Btn'] = Inc\OrderOperateInc::orderInc($values['order_status'], 'adminActBtn');
                //回访标识
//                $orderListArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? Inc\OrderStatus::getVisitName($values['visit_id']):Inc\OrderStatus::getVisitName(Inc\OrderStatus::visitUnContact);

               $orderOperateData  = self::getOrderOprate($values['order_no']);

                $orderListArray['data'][$keys]['act_state'] = $orderOperateData['button_operate'] ?? '';
                $orderListArray['data'][$keys]['logistics_info'] = $orderOperateData['logistics_info'] ?? '';
                $orderListArray['data'][$keys]['zm_order_no'] = $orderOperateData['zm_order_no'] ?? '';
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
     * 获取线下门店后台订单列表
     * Author: heaven
     * @param array $param
     * @return array
     */
    public static function getOfflineOrderList($param = array())
    {


        //根据用户id查找订单列表

        $orderListArray = OrderRepository::getAdminOrderList($param);
//        dd($orderListArray);


//        $orderListArray = objectToArray($orderList);

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
                //发货时间
                $orderListArray['data'][$keys]['predict_delivery_time'] = date("Y-m-d H:i:s", $values['predict_delivery_time']);
                //风控审核状态名称
                $orderListArray['data'][$keys]['risk_check_name'] = Inc\OrderRiskCheckStatus::getStatusName($values['risk_check']);


                //设备名称

                //订单商品列表相关的数据
                $actArray = Inc\OrderOperateInc::orderInc($values['order_status'], 'offlineOrderBtn');





                $orderListArray['data'][$keys]['admin_Act_Btn'] = $actArray;
                //回访标识
                $orderListArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? Inc\OrderStatus::getVisitName($values['visit_id']):Inc\OrderStatus::getVisitName(Inc\OrderStatus::visitUnContact);

                //$orderListArray['data'][$keys]['act_state'] = self::getOrderOprate($values['order_no']);

            }

        }

        $orderListArray =  self::getManageOffLineGoodsActState($orderListArray);

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

        $orderListArray = OrderRepository::getAdminOrderList($param);
//        dd($orderListArray);


//        $orderListArray = objectToArray($orderList);

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
                //发货时间
                $orderListArray['data'][$keys]['predict_delivery_time'] = date("Y-m-d H:i:s", $values['predict_delivery_time']);
                //风控审核状态名称
                $orderListArray['data'][$keys]['risk_check_name'] = Inc\OrderRiskCheckStatus::getStatusName($values['risk_check']);


                //设备名称

                //订单商品列表相关的数据
                $actArray = Inc\OrderOperateInc::orderInc($values['order_status'], 'adminActBtn');




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

        $orderListArray =  self::getManageGoodsActAdminState($orderListArray);

        return apiResponseArray(ApiStatus::CODE_0,$orderListArray);


    }


    /**
     * 获取后台订单列表
     * Author: heaven
     * @param array $param
     * @return array
     */
    public static function getOrderExportList($param = array(),$pagesize=5)
    {
        //根据用户id查找订单列表

        $orderListArray = OrderRepository::getAdminExportOrderList($param, $pagesize);

        if (empty($orderListArray)) return false;

        $goodsData =  self::getExportActAdminState(array_keys($orderListArray), $actArray=array());


        if (!empty($orderListArray)) {

            foreach ($orderListArray as $keys=>$values) {

                //订单状态名称
                $orderListArray[$keys]['order_status_name'] = Inc\OrderStatus::getStatusName($values['order_status']);
                //支付方式名称
                $orderListArray[$keys]['pay_type_name'] = Inc\PayInc::getPayName($values['pay_type']);
                //应用来源
                $orderListArray[$keys]['appid_name'] = OrderInfo::getAppidInfo($values['appid']);

                $orderListArray[$keys]['goodsInfo'] = $goodsData[$keys]['goodsInfo'];
                //发货时间
                $orderListArray[$keys]['predict_delivery_time'] = date("Y-m-d H:i:s", $values['predict_delivery_time']);
                //芝麻分

               $zhimaScore =  OrderOperate::getOrderRiskScore($keys);

               $orderListArray[$keys]['zhima_score'] = $zhimaScore;

                //回访标识
                $orderListArray[$keys]['visit_name'] = !empty($values['visit_id'])? Inc\OrderStatus::getVisitName($values['visit_id']):Inc\OrderStatus::getVisitName(Inc\OrderStatus::visitUnContact);

            }


        }

        return $orderListArray;


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
   public static function getGoodsListActState($orderNo, $actArray, $goodsExtendArray=array(), $payType='')
   {

       $goodsList = OrderRepository::getGoodsListByOrderId($orderNo);

       if (empty($goodsList)) return [];
           //到期时间多于1个月不出现到期处理
           //获取还机单信息
           $orderGivebackService = new OrderGiveback();//创建还机单服务层
           foreach($goodsList as $keys=>$values) {
               $goodsList[$keys]['specs'] = filterSpecs($values['specs']);
               $goodsList[$keys]['left_zujin'] = '';


               $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($values['goods_no']);
               $goodsList[$keys]['give_back_status'] = '';
               $goodsList[$keys]['evaluation_status'] = '';
               if ($orderGivebackInfo) {
                   $goodsList[$keys]['give_back_status'] = $orderGivebackInfo['status'];
                   $goodsList[$keys]['evaluation_status'] = $orderGivebackInfo['evaluation_status'];

               }

               //获取ime信息
               $imeInfo = [];
               $imeInfo =    DeliveryDetail::getGoodsDeliveryInfo($orderNo,$values['goods_no']);
               if ($imeInfo) {

                   $imeInfo = $imeInfo->getData();
               } 

               $goodsList[$keys]['imei'] =   $imeInfo['imei1'] ?? '';
               $goodsList[$keys]['serial_number'] =   $imeInfo['serial_number']?? '';
               if ($goodsExtendArray) {

                   $goodsList[$keys]['firstAmount'] = $goodsExtendArray[$values['goods_no']]['firstAmount'];
                   $goodsList[$keys]['firstInstalmentDate'] = $goodsExtendArray[$values['goods_no']]['firstInstalmentDate'];

               } else {

                   if ($payType==Inc\PayInc::PcreditPayInstallment) {

                       //如果是花呗先享月租金+碎屏保
                       if ($values['zuqi_type']==Inc\OrderStatus::ZUQI_TYPE_DAY) {

                           $goodsList[$keys]['firstAmount'] = normalizeNum($values['amount_after_discount']+$values['insurance']);
                       } else {

                           $goodsList[$keys]['firstAmount'] = normalizeNum(($values['amount_after_discount']+$values['insurance'])/$values['zuqi']);
                       }


                   }

               }

               //处于租期中，获取剩余未支付租金
               if($values['goods_status']>=Inc\OrderGoodStatus::RENTING_MACHINE) {
                   $instaulment = OrderGoodsInstalmentRepository::getSumAmount($values['goods_no']);
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
//                $endTime = strtotime(date("Y-m-d",$values['end_time']));
//                $todayTime = strtotime(date("Y-m-d",time()));
//                //时间未到期  ,true未到期
//               $notInTimeToGive =   ( $endTime - intval(config('web.day_expiry_process_days')) > $todayTime) ?? false;
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
                  $isCustomer = ($values['goods_status']>=Inc\OrderGoodStatus::REFUNDS && $values['goods_status']<=Inc\OrderGoodStatus::EXCHANGE_REFUND) ?? false;

                   //无分期或者分期已全部还完不出现提前还款按钮
                   $orderInstalmentData = OrderGoodsInstalment::queryList(array('order_no'=>$orderNo,'goods_no'=>$values['goods_no'],  'status'=>Inc\OrderInstalmentStatus::UNPAID));
                   if (empty($orderInstalmentData) || $payType==Inc\PayInc::FlowerFundauth){
                       $goodsList[$keys]['act_goods_state']['prePay_btn'] = false;
                   }

                   if ($values['zuqi_type']== Inc\OrderStatus::ZUQI_TYPE1) {

                       //申请售后没有
                       $goodsList[$keys]['act_goods_state']['service_btn'] = false;
                       //到期处理

//                       if (($values['end_time']>time()+config('web.day_expiry_process_days')) || $isCustomer) {
//                           $goodsList[$keys]['act_goods_state']['expiry_process'] = false;
//                       }

                       if ($isCustomer) {
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
                       if (($values['end_time'] > 0 && ($values['end_time'] - config('web.month_expiry_process_days')) > time()) || $isCustomer) {
                           $goodsList[$keys]['act_goods_state']['expiry_process'] = false;
                       }
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
     * 获取线下门店后台设置的操作列表
     * Author: heaven
     * @param $orderNo
     * @param $actArray
     * @return array|bool
     */
    public static function getManageOffLineGoodsActState($orderListArray)
    {
        $goodsList = OrderRepository::getGoodsListByOrderIdArray($orderListArray['orderIds'], array('goods_yajin','yajin','discount_amount','amount_after_discount',
            'goods_status','coupon_amount','goods_name','goods_no','specs','zuqi','zuqi_type','order_no','surplus_yajin'));
        if (empty($goodsList)) return [];
        $goodsList = array_column($goodsList,NULL,'goods_no');
        //到期时间多于1个月不出现到期处理
        foreach($goodsList as $keys=>$values) {
            $actArray = $orderListArray['data'][$values['order_no']]['admin_Act_Btn'];
            $goodsList[$keys]['less_yajin'] = normalizeNum($values['goods_yajin']-$values['yajin']);
            $goodsList[$keys]['specs'] = filterSpecs($values['specs']);
            $goodsList[$keys]['market_zujin'] = normalizeNum($values['amount_after_discount']+$values['coupon_amount']+$values['discount_amount']);
            if (empty($actArray)){
                $goodsList[$keys]['act_goods_state']= [];
            } else {

                $goodsList[$keys]['act_goods_state']= $actArray;


                //创建服务层对象
                $orderGivebackService = new OrderGiveback();
                //获取还机单基本信息
                $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo( $values['goods_no'] );
                if ($orderGivebackInfo) {

                    if ($orderGivebackInfo['status'] == Inc\OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK){
                        $goodsList[$keys]['act_goods_state']['check_btn'] = true;
                        $orderListArray['data'][$values['order_no']]['order_status_name'] = Inc\OrderGivebackStatus::getStatusList(Inc\OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK);
                    }

                    if (in_array($orderGivebackInfo['evaluation_status'], array(Inc\OrderGivebackStatus::EVALUATION_STATUS_QUALIFIED, Inc\OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED))){
                        $goodsList[$keys]['act_goods_state']['check_result_btn'] = true;
                        //检测不合格并且还机属于待支付，显示待赔付
                        if ($orderGivebackInfo['evaluation_status']==Inc\OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && $orderGivebackInfo['status'] == Inc\OrderGivebackStatus::STATUS_DEAL_WAIT_PAY)
                        {
                            $orderListArray['data'][$values['order_no']]['order_status_name'] = '待用户赔付';
                        }

                    }

                }

                if ($orderListArray['data'][$values['order_no']]['order_status']==Inc\OrderStatus::OrderInService) {
                    if ($orderListArray['data'][$values['order_no']]['pay_type'] == Inc\PayInc::FlowerFundauth)
                    {

                        if ($goodsList[$keys]['zuqi_type']==Inc\OrderStatus::ZUQI_TYPE1) {

                            $goodsList[$keys]['yajin'] = normalizeNum($goodsList[$keys]['yajin']+$goodsList[$keys]['amount_after_discount']);
                        }

                    }


                }

            }

            $orderListArray['data'][$values['order_no']]['goodsInfo'][$keys] = $goodsList[$keys];

        }
        if (isset($orderListArray['orderIds'])) {
            unset($orderListArray['orderIds']);
        }

        return $orderListArray;


    }


    /**
     * 获取后台设置的操作列表
     * Author: heaven
     * @param $orderNo
     * @param $actArray
     * @return array|bool
     */
    public static function getManageGoodsActAdminState($orderListArray)
    {

        $goodsList = OrderRepository::getGoodsListByOrderIdArray($orderListArray['orderIds'], array('goods_yajin','yajin','discount_amount','amount_after_discount',
            'goods_status','coupon_amount','goods_name','goods_no','specs','goods_thumb','zuqi','zuqi_type','order_no','surplus_yajin'));
        if (empty($goodsList)) return [];
        $goodsList = array_column($goodsList,NULL,'goods_no');
        //到期时间多于1个月不出现到期处理
        foreach($goodsList as $keys=>$values) {
            $actArray = $orderListArray['data'][$values['order_no']]['admin_Act_Btn'];
            $goodsList[$keys]['less_yajin'] = normalizeNum($values['goods_yajin']-$values['yajin']);
            $goodsList[$keys]['specs'] = filterSpecs($values['specs']);
            $goodsList[$keys]['market_zujin'] = normalizeNum($values['amount_after_discount']+$values['coupon_amount']+$values['discount_amount']);
            if (empty($actArray)){
                $goodsList[$keys]['act_goods_state']= [];
            } else {

                $goodsList[$keys]['act_goods_state']= $actArray;
                //是否处于售后之中
                //是否处于还机之中
                if (in_array($values['goods_status'],array(Inc\OrderGoodStatus::BACK_IN_THE_MACHINE, Inc\OrderGoodStatus::COMPLETE_THE_MACHINE, Inc\OrderGoodStatus::CLOSED_THE_MACHINE))) {

                    $goodsList[$keys]['act_goods_state']['offline_giveback_btn'] = false;
                }

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

                if ($orderListArray['data'][$values['order_no']]['order_status']==Inc\OrderStatus::OrderInService) {
                    if ($orderListArray['data'][$values['order_no']]['pay_type'] == Inc\PayInc::FlowerFundauth)
                    {

                        if ($goodsList[$keys]['zuqi_type']==Inc\OrderStatus::ZUQI_TYPE1) {

                            $goodsList[$keys]['yajin'] = normalizeNum($goodsList[$keys]['yajin']+$goodsList[$keys]['amount_after_discount']);
                        }

                    }

                    $insuranceData = self::getInsuranceInfo(['order_no'  => $values['order_no'] , 'goods_no'=>$values['goods_no']],array('type'));
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

//                $orderInstalmentData = OrderGoodsInstalment::queryList(array('order_no'=>$orderNo,'goods_no'=>$values['goods_no'],  'status'=>Inc\OrderInstalmentStatus::UNPAID));


            }


            $orderListArray['data'][$values['order_no']]['goodsInfo'][$keys] = $goodsList[$keys];

        }
        if (isset($orderListArray['orderIds'])) {
            unset($orderListArray['orderIds']);
        }

        return $orderListArray;


    }




    /**
     * 获取后台设置的操作列表
     * Author: heaven
     * @param $orderNo
     * @param $actArray
     * @return array|bool
     */
    public static function getExportActAdminState($orderIds, $actArray)
    {

        $goodsList = OrderRepository::getGoodsListByOrderIdArray($orderIds,array('goods_name','zuqi','zuqi_type','specs','order_no','insurance_cost'));

        if (empty($goodsList)) return [];
        $goodsList = array_column($goodsList,NULL,'goods_no');
        $orderListArray = array();
        //到期时间多于1个月不出现到期处理
        foreach($goodsList as $keys=>$values) {
            $goodsList[$keys]['specs'] = filterSpecs($values['specs']);
            $goodsList[$keys]['zuqi_name'] = $values['zuqi'].Inc\OrderStatus::getZuqiTypeName($values['zuqi_type']);
            $orderListArray[$values['order_no']]['goodsInfo'][$keys] = $goodsList[$keys];
        }
        return $orderListArray;


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
		try{
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
		} catch (\App\Lib\NotFoundException $ex) {
			//支付单不存在，默认无需进行支付等操作
			return [
				'withholdStatus' => false,
				'paymentStatus' => false,
				'fundauthStatus' => false,
			];
		}
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