<?php
/**
 *    订单操作类
 *    author: wuhaiyan
 *    date : 2018-06-04
 */
namespace App\Order\Modules\Service;

use App\Lib\Common\LogApi;
use App\Lib\Coupon\Coupon;
use App\Lib\Goods\Goods;
use App\Order\Models\Order;
use App\Order\Models\OrderBuyout;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderReturn;
use App\Order\Modules\Inc;
use App\Order\Modules\Inc\OrderGivebackStatus;
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
                $b = OrderOperate::orderUnblind($v);
                if(!$b){
                    DB::rollBack();
                    return ApiStatus::CODE_31008;
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
     *  定时任务确认收货
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
                if(!$b){
                    LogApi::debug("订单确认收货失败:" . $v['order_no']);
                }

            }

    }
    /**
     * 定时任务  长租订单到期前一个月发送信息
     */
    public static function cronOneMonthEndByLong()
    {

        $whereLong =[];
        $whereLong[] = ['goods_status', '=', Inc\OrderGoodStatus::RENTING_MACHINE];
        $whereLong[] = ['zuqi_type', '=', 2];
        $start =strtotime(date('Y-m-d',strtotime('+1 month')));
        $end =strtotime(date('Y-m-d 23:59:59',strtotime('+1 month')));
        $goodsData = OrderGoods::query()->where($whereLong)->whereBetween('end_time',[$start,$end])->get()->toArray();
        if (!$goodsData) {
            echo "无";die;
        }
        foreach ($goodsData as $k => $v) {
            //发送短信
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no'],SceneConfig::ORDER_MONTH_BEFORE_MONTH_ENDING);
            $orderNoticeObj->notify();
        }
        echo "完成";die;


    }
    /**
     * 定时任务  长租订单到期前一周发送信息 + 短租 提前一天发送短信
     */
    public static function cronOneWeekEndByLong()
    {

        $whereLong =[];
        $whereLong[] = ['goods_status', '=', Inc\OrderGoodStatus::RENTING_MACHINE];
        $whereLong[] = ['zuqi_type', '=', 2];
        $start =strtotime(date('Y-m-d',strtotime('+1 week')));
        $end =strtotime(date('Y-m-d 23:59:59',strtotime('+1 week')));
        $goodsData = OrderGoods::query()->where($whereLong)->whereBetween('end_time',[$start,$end])->get()->toArray();
        if (!$goodsData) {
            echo "无";die;
        }
        foreach ($goodsData as $k => $v) {
            //发送短信
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no'],SceneConfig::ORDER_MONTH_BEFORE_WEEK_ENDING);
            $orderNoticeObj->notify();
        }
        $whereSort =[];
        $whereSort[] = ['goods_status', '=', Inc\OrderGoodStatus::RENTING_MACHINE];
        $whereSort[] = ['zuqi_type', '=', 1];
        $start =strtotime(date('Y-m-d',strtotime('+1 days')));
        $end =strtotime(date('Y-m-d 23:59:59',strtotime('+1 days')));
        $goodsData = OrderGoods::query()->where($whereSort)->whereBetween('end_time',[$start,$end])->get()->toArray();
        if (!$goodsData) {
            echo "无";die;
        }
        foreach ($goodsData as $k => $v) {
            //发送短信
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no'],SceneConfig::ORDER_MONTH_BEFORE_WEEK_ENDING);
            $orderNoticeObj->notify();
        }
        echo "完成";die;
    }
    /**
     * 定时任务  长租订单逾期一个月发送信息
     */
    public static function cronOverOneMonthEndByLong()
    {
        $whereLong =[];
        $whereLong[] = ['goods_status', '=', Inc\OrderGoodStatus::RENTING_MACHINE];
        $whereLong[] = ['zuqi_type', '=', 2];
        $start =strtotime(date('Y-m-d',strtotime('-1 month')));
        $end =strtotime(date('Y-m-d 23:59:59',strtotime('-1 month')));
        $goodsData = OrderGoods::query()->where($whereLong)->whereBetween('end_time',[$start,$end])->get()->toArray();
        if (!$goodsData) {
            echo "无";die;
        }
        foreach ($goodsData as $k => $v) {
            //发送短信
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no'],SceneConfig::ORDER_MONTH_OVER_MONTH_ENDING);
            $orderNoticeObj->notify();
        }
        echo "成功";die;
    }
    /**
     *  定时任务取消买断支付单
     * @return bool
     */
    public static function cronCancelOrderBuyout()
    {
        //设置未支付和超时条件
        $where[] = ['status', '=', Inc\OrderBuyoutStatus::OrderInitialize];
        $where[] = ['create_time', '<', time() - 600,];
        $orderList = OrderBuyout::query()->where($where)->limit(100)->get();
        if (!$orderList) {
            return false;
        }
        $orderList = $orderList->toArray();
        //批量取消买断单
        foreach($orderList as $value){
            DB::beginTransaction();
            //取消买断单
            $condition = [
                'id'=>$value['id'],
                'status'=>Inc\OrderBuyoutStatus::OrderInitialize,
            ];
            $ret = OrderBuyout::where($condition)->update(['status'=>Inc\OrderBuyoutStatus::OrderCancel,'update_time'=>time()]);
            if(!$ret){
                DB::rollBack();
                continue;
            }
            //更新订单商品状态
            $data = [
                'business_key' => '',
                'business_no' => '',
                'goods_status'=>Inc\OrderGoodStatus::RENTING_MACHINE,
                'update_time'=>time()
            ];
            $ret = OrderGoods::where(['goods_no'=>$value['goods_no'],'goods_status'=>Inc\OrderGoodStatus::BUY_OFF])->update($data);
            if(!$ret){
                DB::rollBack();
                continue;
            }
            //解冻订单状态
            $OrderRepository= new OrderRepository;
            $ret = $OrderRepository->orderFreezeUpdate($value['order_no'],Inc\OrderFreezeStatus::Non);
            if(!$ret){
                DB::rollBack();
                continue;
            }
            DB::commit();
        }
        return true;
    }
	/**
	 * 还机单需要支付时逾期处理
	 * @param type $param
	 */
	public static function cronGivebackAgedFail( ) {
		//查询逾期的还机单【支付状态：支付中，支付时间与当前时间间隔一周以上的】
		$orderGivebackService = new OrderGiveback();
		$where = [
			'payment_status' => OrderGivebackStatus::PAYMENT_STATUS_IN_PAY,
			'payment_end_time' => time() - 7*3600,
		];
		DB::beginTransaction();
		try{
			do{
				//一页一页的查询处理，处理完成后，还机单状态会更新，所以永远查询第一页数据
				$orderGivebackList = $orderGivebackService->getList($where,['page'=>1]);
				$orderGivebackListArr = $orderGivebackList['data'];//逾期的还机单列表
				$total = $orderGivebackList['total'];//逾期的还机单总数
				$lastPage = $orderGivebackList['last_page'];//逾期的还机单最后一页列表
				if($total){
					//-+----------------------------------------------------------------
					// | 逾期处理：更新支付状态=》未支付，还机单状态=>逾期违约
					//-+----------------------------------------------------------------
					foreach ($orderGivebackListArr as $orderGiveBackInfo) {
						//更新商品表状态
						$orderGoods = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($orderGiveBackInfo['goods_no']);
						if( !$orderGoods ){
							throw new Exception('商品信息获取失败：'.$orderGiveBackInfo['goods_no']);
						}
						$orderGoodsResult = $orderGoods->givebackClose();
						if(!$orderGoodsResult){
							throw new \Exception('商品状态更新，还机关闭：'.$orderGiveBackInfo['goods_no']);
						}
						//解冻订单
						if(!OrderGiveback::__unfreeze($orderGiveBackInfo['order_no'])){
							throw new \Exception('订单解冻失败：'.$orderGiveBackInfo['order_no']);
						}

						//更新还机单
						$orderGivebackUpdate = $orderGivebackService->update(['giveback_no'=>$orderGiveBackInfo['giveback_no']], [
							'payment_status' => OrderGivebackStatus::PAYMENT_STATUS_NOT_PAY,
							'payment_time' => time(),
							'status' => OrderGivebackStatus::STATUS_AGED_FAIL,
						]);
						if(!$orderGivebackUpdate){ 
							throw new \Exception('还机单状态更新失败：'.$orderGiveBackInfo['giveback_no']);
						}
					}
				}
			}
			//只要逾期的还机单总数不为空;并且最后一页不是第一页继续查询
			while ($total && $lastPage!=1);
			
		} catch (\Exception $ex) {
			DB::rollBack();
			\App\Lib\Common\LogApi::debug('[还机单逾期违约]', ['msg'=>$ex->getMessage()]);
		}
		DB::commit();
	}

    /**
     *  定时任务 换货确认收货
     * @return bool
     */
    public static function cronBarterDelivey()
    {
        $whereLong =[];
        $whereLong[] = ['status', '=', Inc\ReturnStatus::ReturnDelivery];
        $whereLong[] = ['delivery_time', '<=', time()-config('web.long_confirm_days')];

        $returnData = OrderReturn::query()->where($whereLong)->get()->toArray();
        if (!$returnData) {
            return false;
        }
        //var_dump($orderData);die;
        foreach ($returnData as $k => $v) {
            $userinfo['uid']=1;
            $userinfo['username']="系统";
            $userinfo['type']=\App\Lib\PublicInc::Type_System;
            $b =OrderReturnCreater::updateorder($v['refund_no'],$userinfo);
            if(!$b){
                LogApi::debug("换货确认收货失败:" . $v['order_no']);
            }

        }

    }

}