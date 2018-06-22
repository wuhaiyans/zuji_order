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
use App\Lib\Warehouse\Delivery;
use App\Order\Controllers\Api\v1\ReturnController;
use App\Order\Models\OrderExtend;
use App\Order\Models\OrderInsurance;
use App\Order\Models\OrderVisit;
use App\Order\Modules\Inc;
use App\Order\Modules\PublicInc;
use App\Order\Modules\Repository\Order\DeliveryDetail;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderReturnRepository;
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
     * [
     *      'type'=>发货类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,//用户ID
     *      'user_name'=>1,//用户名
     * ]
     * @return boolean
     */

    public static function delivery($orderDetail,$goodsInfo,$operatorInfo=[]){
        DB::beginTransaction();
        try{
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

                    OrderLogRepository::add($operatorInfo['user_id'],$operatorInfo['user_name'],$operatorInfo['type'],$orderDetail['order_no'],"发货","");
                }

                DB::commit();

                //增加确认收货队列
                $confirmTime =$orderInfo['zuqi_type'] ==1?config('web.short_confirm_days'):config('web.long_confirm_days');

                $b =JobQueueApi::addScheduleOnce(config('app.env')."DeliveryReceive".$orderDetail['order_no'],config("tripartite.API_INNER_URL"), [
                    'method' => 'api.inner.deliveryReceive',
                    'order_no'=>$orderDetail['order_no'],
                ],time()+$confirmTime,"");

                return true;

            }else {
                //判断订单冻结类型 冻结就走换货发货
                $b = OrderReturnCreater::createchange($orderDetail['order_no'], $goodsInfo);
                if (!$b) {
                    DB::rollBack();
                    return false;
                }
                DB::commit();
                return true;
            }
        }catch (\Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;
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
        $whereArray[] = ['goods_no', '=', $params['goods_no']];
        $insuranceData =  OrderInsurance::where($whereArray)->first();
        $data = array();
        if ($insuranceData) {
            $data = $insuranceData->toArray();
            $data['typeName'] =  getInsuranceTypeName($data['type']);

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
            $params['create_time'] =time();
            $order = OrderVisit::updateOrCreate($params);
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

        if(empty($orderNo)){return false;}
        DB::beginTransaction();
        try{
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


            $params=[
                    'order_no'=>$orderNo,//
                    'receive_type'=>$userType,//类型：String  必有字段  备注：签收类型1管理员，2用户,3系统，4线下
                    'user_id'=>$userId,//
                    'user_name'=>$userName,//
            ];

            //通知给收发货系统
            $b =Delivery::orderReceive($params);
            if(!$b){
                LogApi::info("收发货系统确认收货失败");
                DB::rollBack();
                return false;
            }
            //插入操作日志
            OrderLogRepository::add($userId,$userName,$userType,$orderNo,"确认收货",$remark);


            DB::commit();
            return true;
        }catch (\Exception $exc){
            DB::rollBack();
            echo $exc->getMessage();
            die;

        }

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
    public static function cancelOrder($orderNo,$userId='')
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
            return ApiStatus::CODE_0;

        } catch (\Exception $exc) {
            DB::rollBack();
            return  ApiStatus::CODE_31006;
        }

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
        $order['instalment_info'] = $goodsExtendData;
        $orderData['instalment_unpay_amount'] = 0.00;
        $orderData['instalment_payed_amount'] = 0.00;
        if ($goodsExtendData) {
            $instalmentUnpayAmount  = 0.00;
            $instalmentPayedAmount  = 0.00;
            foreach ($goodsExtendData as $keys=> $goodsValues) {
                if (is_array($goodsValues)) {
                    foreach($goodsValues as $values) {


                        if ($values['status']==Inc\OrderInstalmentStatus::SUCCESS)
                        {

                            $instalmentPayedAmount+=$values['amount'];
                        } else {

                            $instalmentUnpayAmount+=$values['amount'];
                        }
                    }

                }
            }
            //未支付总金额
            $orderData['instalment_unpay_amount'] = normalizeNum($instalmentUnpayAmount);
            //已支付总金额
            $orderData['instalment_payed_amount'] = normalizeNum($instalmentPayedAmount);
        }


        //订单状态名称
        $orderData['order_status_name'] = Inc\OrderStatus::getStatusName($orderData['order_status']);

        //支付方式名称
        $orderData['pay_type_name'] = Inc\PayInc::getPayName($orderData['pay_type']);

        //应用来源
        $orderData['appid_name'] = OrderInfo::getAppidInfo($orderData['appid']);


        //订单金额
        $orderData['order_gooods_amount']  = $orderData['order_amount']+$orderData['coupon_amount']+$orderData['discount_amount']+$orderData['order_insurance'];
        //支付金额
        $orderData['pay_amount']  = $orderData['order_amount']+$orderData['order_insurance'];
        //总租金
        $orderData['zujin_amount']  =   $orderData['order_amount'];
        //碎屏意外险
        $orderData['order_insurance_amount']  =   $orderData['order_insurance'];
        //授权总金额
        $orderData['zujin_amount']  =   $orderData['order_yajin'];

        $orderData['certified_platform_name']  =   Certification::getPlatformName($orderData['certified_platform']);
        //

        $order['order_info'] = $orderData;

        //订单商品列表相关的数据
        $actArray = Inc\OrderOperateInc::orderInc($orderData['order_status'], 'actState');

        $goodsData =  self::getGoodsListActState($orderNo, $actArray);

        if (empty($goodsData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['goods_info'] = $goodsData;
        //设备扩展信息表
        $goodsExtendData =  OrderRepository::getGoodsDeliverInfo($orderNo);
//        p($goodsExtendData);
        $order['goods_extend_info'] = $goodsExtendData;

        return apiResponseArray(ApiStatus::CODE_0,$order);
//        return $orderData;

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

        $orderList = OrderRepository::getClientOrderList($param);

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

                $orderListArray['data'][$keys]['admin_Act_Btn'] = Inc\OrderOperateInc::orderInc($values['order_status'], 'adminActBtn');
                //回访标识
//                $orderListArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? Inc\OrderStatus::getVisitName($values['visit_id']):Inc\OrderStatus::getVisitName(Inc\OrderStatus::visitUnContact);

                $orderListArray['data'][$keys]['act_state'] = self::getOrderOprate($values['order_no']);

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

                //设备名称

                //订单商品列表相关的数据
                $actArray = Inc\OrderOperateInc::orderInc($values['order_status'], 'adminActBtn');


                $goodsData =  self::getManageGoodsActAdminState($values['order_no'], $actArray);

                $orderListArray['data'][$keys]['goodsInfo'] = $goodsData;

                $orderListArray['data'][$keys]['admin_Act_Btn'] = Inc\OrderOperateInc::orderInc($values['order_status'], 'adminActBtn');
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

        $orderData   =  self::getOrderInfo($orderNo);

        $orderData  =   $orderData['data'];
        if (empty($orderData['order_info'])) return [];
        $actArray   =   Inc\OrderOperateInc::orderInc($orderData['order_info']['order_status'], 'actState');
        //长期租用中七天之内出现售后
        if ($orderData['order_info']['zuqi_type'] == Inc\OrderStatus::ZUQI_TYPE_MONTH &&
            $orderData['order_info']['order_status'] == Inc\OrderStatus::OrderInService)
        {

            //收货后超过7天不出现售后按钮
            if (time()-config('web.month_service_days')>$orderData['order_info']['receive_time'] && $orderData['order_info']['receive_time']>0) {
                unset($actArray['service_btn']);
                unset($actArray['expiry_process']);
            }

        }

            return $actArray;


    }


    /**
     * 获取客户端设置的操作列表
     * Author: heaven
     * @param $orderNo
     * @param $actArray
     * @return array|bool
     */
   public static function getGoodsListActState($orderNo, $actArray)
   {

       $goodsList = OrderRepository::getGoodsListByOrderId($orderNo);
       if (empty($goodsList)) return [];

           //到期时间多于1个月不出现到期处理
           foreach($goodsList as $keys=>$values) {
               $goodsList[$keys]['less_yajin'] = normalizeNum($values['goods_yajin']-$values['yajin']);
               $goodsList[$keys]['market_zujin'] = normalizeNum($values['amount_after_discount']+$values['coupon_amount']+$values['discount_amount']);
               if (empty($actArray)){
                   $goodsList[$keys]['act_goods_state']= [];
               } else {

                   $goodsList[$keys]['act_goods_state']= $actArray;
                   //是否处于售后之中
                   $expire_process = intval($values['goods_status']) >= Inc\OrderGoodStatus::EXCHANGE_GOODS ?? false;
                   if (((time()+config('web.month_expiry_process_days'))< $values['end_time'] && $values['end_time']>0)
                       || $expire_process
                   ) {
                       unset($goodsList[$keys]['act_goods_state']['expiry_process']);
                   }
                   //无分期或者分期已全部还完不出现提前还款按钮

                   $orderInstalmentData = OrderGoodsInstalment::queryList(array('order_no'=>$orderNo,'goods_no'=>$values['goods_no'],  'status'=>Inc\OrderInstalmentStatus::UNPAID));
                   if (empty($orderInstalmentData)){
                       unset($goodsList[$keys]['act_goods_state']['prePay_btn']);
                   }

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
            $goodsList[$keys]['market_zujin'] = normalizeNum($values['amount_after_discount']+$values['coupon_amount']+$values['discount_amount']);
            if (empty($actArray)){
                $goodsList[$keys]['act_goods_state']= [];
            } else {

                $goodsList[$keys]['act_goods_state']= $actArray;
                //是否处于售后之中
                $expire_process = intval($values['goods_status']) >= Inc\OrderGoodStatus::EXCHANGE_GOODS ?? false;
                if ($expire_process) {
                    $goodsList[$keys]['act_goods_state']['buy_off'] = false;
                }
                //是否已经操作过保险

                $insuranceData = self::getInsuranceInfo(['order_no'  => $orderNo , 'goods_no'=>$values['goods_no']]);
//                $orderInstalmentData = OrderGoodsInstalment::queryList(array('order_no'=>$orderNo,'goods_no'=>$values['goods_no'],  'status'=>Inc\OrderInstalmentStatus::UNPAID));
                if ($insuranceData){
                    $goodsList[$keys]['act_goods_state']['Insurance'] = false;
                    $goodsList[$keys]['act_goods_state']['alreadyInsurance'] = true;
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




}