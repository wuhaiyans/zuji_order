<?php
/**
 *    订单操作类
 *    author: heaven
 *    date : 2018-05-04
 */
namespace App\Order\Modules\Service;

use App\Lib\Common\LogApi;
use App\Lib\Coupon\Coupon;
use App\Lib\Goods\Goods;
use App\Order\Models\Order;
use App\Order\Modules\Inc;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\WithholdQuery;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use Illuminate\Support\Facades\DB;
use App\Lib\ApiStatus;



class CronOperate
{

    /**
     *  定时任务取消订单
     * @return bool
     */
    public static function cronCancelOrder()
    {
        try {
            $param = [
                'order_status' => Inc\OrderStatus::OrderWaitPaying,
                'now_time'=> time() - 7200,
            ];
            $orderData = OrderRepository::getOrderAll($param);
            if (!$orderData) {
                return false;
            }
            //var_dump($orderData);die;
            foreach ($orderData as $k => $v) {
                //开启事物
                DB::beginTransaction();
                $b = OrderRepository::closeOrder($v['order_no']);
                if(!$b){
                    DB::rollBack();
                    LogApi::debug("更改订单状态失败:" . $v['order_no']);
                }
                $isInstalment   =   OrderGoodsInstalmentRepository::queryCount(['order_no'=>$v['order_no']]);
                if ($isInstalment) {
                    $success =  Instalment::close(['order_no'=>$v['order_no']]);
                    if (!$success) {
                        DB::rollBack();
                        LogApi::debug("订单关闭分期失败:" . $v['order_no']);
                    }
                }
                DB::commit();
                //释放库存
                //查询商品的信息
                $orderGoods = OrderRepository::getGoodsListByOrderId($v['order_no']);
                if ($orderGoods) {
                    foreach ($orderGoods as $orderGoodsValues) {
                        //暂时一对一
                        $goods_arr[] = [
                            'sku_id' => $orderGoodsValues['zuji_goods_id'],
                            'spu_id' => $orderGoodsValues['prod_id'],
                            'num' => $orderGoodsValues['quantity']
                        ];
                    }
                    $success = Goods::addStock($goods_arr);
                    if (!$success)
                        //DB::rollBack();
                        LogApi::debug("订单恢复库存失败:" . $v['order_no']);
                }

                //支付方式为代扣 需要解除订单代扣
                if($v['pay_type'] == Inc\PayInc::WithhodingPay){
                    //查询是否签约代扣 如果签约 解除代扣
                    try{
                        $withhold = WithholdQuery::getByBusinessNo(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no']);
                        $params =[
                            'business_type' =>Inc\OrderStatus::BUSINESS_ZUJI,	// 【必须】int		业务类型
                            'business_no'	=>$v['order_no'],	// 【必须】string	业务编码
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
            $orderCouponData = OrderRepository::getCouponListByOrderId($v['order_no']);
            if ($orderCouponData) {
                $coupon_id = array_column($orderCouponData, 'coupon_id');
                $success = Coupon::setCoupon(['user_id' => $v['user_id'], 'coupon_id' => $coupon_id]);

                if ($success) {
                    DB::rollBack();
                    LogApi::debug("订单优惠券恢复失败:" . $v['order_no']);
                }

            }

                // 订单取消后发送取消短息。;
                $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no'],SceneConfig::ORDER_CANCEL);
                $orderNoticeObj->notify();

        }
        return true;

        } catch (\Exception $exc) {
            DB::rollBack();
            return  ApiStatus::CODE_31006;
        }

    }

    /**
     *  定时任务确认订单
     * @return bool
     */
    public static function cronDeliveryReceive()
    {
            $whereSort =[];
            $whereLong =[];
            $whereSort[] = ['order_status', '=', Inc\OrderStatus::OrderDeliveryed];
            $whereSort[] = ['zuqi_type', '=', 1];
            $whereSort[] = ['delivery_time', '<=', time()-config('web.short_confirm_days')];

            $whereLong[] = ['order_status', '=', Inc\OrderStatus::OrderDeliveryed];
            $whereLong[] = ['zuqi_type', '=', 2];
            $whereLong[] = ['delivery_time', '<=', time()-config('web.long_confirm_days')];


            $orderData = Order::query()->where($whereSort)->orWhere($whereLong)->get()->toArray();
            if (!$orderData) {
                return false;
            }
            //var_dump($orderData);die;
            foreach ($orderData as $k => $v) {
                $params['order_no'] =$v['order_no'];

                $b =OrderOperate::deliveryReceive($params,1);
                var_dump($b);die;
                if(!$b){
                    LogApi::debug("订单确认收货失败:" . $v['order_no']);
                }

            }

    }


}