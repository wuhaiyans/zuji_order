<?php
/**
 *    订单操作类
 *    author: heaven
 *    date : 2018-05-04
 */
namespace App\Order\Modules\Service;

use App\Lib\Coupon\Coupon;
use App\Lib\Goods\Goods;
use App\Lib\Warehouse\Delivery;
use App\Order\Modules\Inc;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderRepository;
use Illuminate\Support\Facades\DB;
use App\Lib\Order\OrderInfo;
use App\Lib\ApiStatus;


class OrderOperate
{
    /**
     * 确认收货接口
     * @param int $orderNo 订单编号
     * @param int $role  在 App\Lib\publicInc 中;
     *  const Type_Admin = 1; //管理员
     *  const Type_User = 2;    //用户
     *  const Type_System = 3; // 系统自动化任务
     *  const Type_Store =4;//线下门店
     * ]
     * @return boolean
     */

    public static function deliveryReceive($orderNo,$role){
        if(empty($orderNo) || empty($role)){return false;}
        DB::beginTransaction();
        try{
            $b =OrderRepository::deliveryReceive($orderNo);
            if(!$b){
                DB::rollBack();
                return false;
            }

            $id =OrderLogRepository::add(0,"",$role,$orderNo,"确认收货","");
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
     * 后台确认订单操作
     * $data =[
     *   'order_no'  => '',//订单编号
     *   'remark'=>'',//操作备注
     * ]
     * @return boolean
     */

    public static function confirmOrder($data){
        if(empty($data)){return false;}
        DB::beginTransaction();
        try{
            $b =OrderRepository::confirmOrder($data['order_no'],$data['remark']);
            if(!$b){
                DB::rollBack();
                return false;
            }

            $delivery =Delivery::apply($data['order_no']);
            if(!$delivery){
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
     * 取消订单
     * Author: heaven
     * @param $orderNo 订单编号
     * @param string $userId 用户id
     * @return bool|string
     */
    public static function cancelOrder($orderNo,$userId='')
    {
        if (empty($orderNo)) {
            return false;
            }
        //开启事物
        DB::beginTransaction();
        try {

            //关闭订单状态
            $orderData =  OrderRepository::closeOrder($orderNo,$userId);
            if (!$orderData) {
                DB::rollBack();
               return ApiStatus::CODE_31002;
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
                $success =Goods::addStock(config('tripartite.Interior_Goods_Request_data'),$goods_arr);


            }

            if ($success || empty($orderGoods)) {
                DB::rollBack();
                return ApiStatus::CODE_31003;
            }
            //优惠券归还

           $success =  Coupon::setCoupon(config('tripartite.Interior_Goods_Request_data'),['user_id'=>$userId ,'coupon_id'=>$orderNo]);

            if ($success) {
                DB::rollBack();
                return ApiStatus::CODE_31003;
            }

            //分期关闭
            $success =  OrderInstalment::close(['order_no'=>$orderNo]);
             if (!$success) {
                 DB::rollBack();
                 return ApiStatus::CODE_31004;
             }
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
        //订单状态名称
        $orderData['order_status_name'] = Inc\OrderStatus::getStatusName($orderData['order_status']);

        //支付方式名称
        $orderData['pay_type_name'] = Inc\PayInc::getPayName($orderData['pay_type']);

        //应用来源
        $orderData['appid_name'] = OrderInfo::getAppidInfo($orderData['appid']);

        $order['order_info'] = $orderData;

        //订单商品列表相关的数据
        $goodsData =  OrderRepository::getGoodsListByOrderId($orderNo);

        if (empty($goodsData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['goods_info'] = $goodsData;
        //设备扩展信息表
        $goodsExtendData =  OrderRepository::getGoodsExtendInfo($orderNo);
        $order['goods_extend_info'] = $goodsExtendData;
        //分期数据表
        $goodsExtendData =  OrderInstalment::queryList(array('order_no'=>$orderNo));
        $order['instalment_info'] = $goodsExtendData;
        return apiResponseArray(ApiStatus::CODE_0,$order);
//        return $orderData;

    }




    /**
     * 获取订单列表
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
                $orderListArray['data'][$keys]['goodsInfo'] = OrderRepository::getGoodsListByOrderId($values['order_no']);
                //回访标识
                $orderListArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? Inc\OrderStatus::getVisitName($values['visit_id']):Inc\OrderStatus::getVisitName(Inc\OrderStatus::visitUnContact);


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
        if (empty($orderNo)) return false;
        $actArray = [];
        $orderData   =  self::getOrderInfo($orderNo);
        if (empty($orderData['order_info'])) return false;
        $actArray   =   Inc\OrderOperateInc::orderInc($orderData['order_info']['order_status'], 'actState');
        //长期租用中七天之内出现售后
        if ($orderData['order_info']['zuqi_type'] == Inc\OrderStatus::ZUQI_TYPE_MONTH &&
            $orderData['order_info']['order_status'] == Inc\OrderStatus::OrderInService)
        {
            //收货后超过7天不出现售后按钮
            if (time()-config('web.month_service_days')>$orderData['order_info']['receive_time'] && $orderData['order_info']['receive_time']>0) {
                unset($actArray['service_btn']);
            }
            //到期时间多于1个月不出现到期处理
            if (time()-config('web.month_expiry_process_days')>$orderData['goods_info']['end_time'] && $orderData['goods_info']['end_time']>0) {
                unset($actArray['service_btn']);
            }

            //无分期或者分期已全部还完不出现提前还款按钮
            $orderInstalmentData = OrderInstalment::queryList(array('order_no'=>$orderNo,'status'=>Inc\OrderInstalmentStatus::UNPAID));
            if (empty($orderInstalmentData)){
                unset($actArray['prePay_btn']);
            }

        }


            return $actArray;


    }




}