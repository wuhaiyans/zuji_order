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
use App\Order\Modules\Repository\OrderOverdueDeductionRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\WithholdQuery;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use Illuminate\Support\Facades\DB;
use App\Lib\ApiStatus;
use \App\Order\Modules\Repository\Order\Order as OrderRespository;


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
            echo "success";die;

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
            echo "success";die;

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
            echo "无长租->";
        }
        foreach ($goodsData as $k => $v) {
            //发送短信
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no'],SceneConfig::ORDER_MONTH_BEFORE_WEEK_ENDING);
            $orderNoticeObj->notify();
        }
        echo "长租完成";
        $whereSort =[];
        $whereSort[] = ['goods_status', '=', Inc\OrderGoodStatus::RENTING_MACHINE];
        $whereSort[] = ['zuqi_type', '=', 1];
        $start =strtotime(date('Y-m-d',strtotime('+1 days')));
        $end =strtotime(date('Y-m-d 23:59:59',strtotime('+1 days')));
        $goodsData = OrderGoods::query()->where($whereSort)->whereBetween('end_time',[$start,$end])->get()->toArray();
        if (!$goodsData) {
            echo "无短租->";
        }
        foreach ($goodsData as $k => $v) {
            //发送短信
            $orderNoticeObj = new OrderNotice(Inc\OrderStatus::BUSINESS_ZUJI,$v['order_no'],SceneConfig::ORDER_DAY_BEFORE_ONE_ENDING);
            $orderNoticeObj->notify();
        }
        echo "短租完成";die;
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
                'business_key' => 0,
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
			'payment_end_time' => time() - 7*24*3600,
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
							throw new \Exception('商品信息获取失败：'.$orderGiveBackInfo['goods_no']);
						}
						$orderGoodsResult = $orderGoods->givebackClose();
						if(!$orderGoodsResult){
							throw new \Exception('商品状态更新，还机关闭：'.$orderGiveBackInfo['goods_no']);
						}
//						//解冻订单
//						if(!OrderGiveback::__unfreeze($orderGiveBackInfo['order_no'])){
//							throw new \Exception('订单解冻失败：'.$orderGiveBackInfo['order_no']);
//						}
						//订单异常关闭
						$orderObj = OrderRespository::getByNo($orderGiveBackInfo['order_no']);
						if( !$orderObj ){
							throw new \Exception('订单信息获取失败：'.$orderGiveBackInfo['order_no']);
						}
						$orderCloseResult = $orderObj->abnormalClose();
						if( !$orderCloseResult ){
							throw new \Exception('订单异常关闭失败：'.$orderGiveBackInfo['order_no']);
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
						//记录日志
						$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
							'order_no'=>$orderGiveBackInfo['order_no'],
							'action'=>'还机单异常关闭',
							'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
							'business_no'=>$orderGiveBackInfo['giveback_no'],
							'goods_no'=>$orderGiveBackInfo['goods_no'],
							'operator_id'=>0,
							'operator_name'=>'任务关闭逾期还机单',
							'operator_type'=>\App\Lib\PublicInc::Type_System,//此处用常量
							'msg'=>'还机单逾期',
						]);
						if( !$goodsLog ){
							\App\Lib\Common\LogApi::debug('[还机支付回调]设备日志记录失败', ['$goodsLog'=>$goodsLog,'$orderGivebackInfo'=>$orderGivebackInfo]);
							throw new \Exception('还机单设备日志记录失败：'.$orderGiveBackInfo['giveback_no']);
						}
					}
				}
			}
			//只要逾期的还机单总数不为空;并且最后一页不是第一页继续查询
			while ($total && $lastPage!=1);
			
		} catch (\Exception $ex) {
			DB::rollBack();
			\App\Lib\Common\LogApi::debug('giveback-cron-agedfail-result', ['msg'=>$ex->getMessage()]);
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
            LogApi::error("[cronBarterDelivey]获取换货信息失败");
            return false;
        }
        //var_dump($orderData);die;
        foreach ($returnData as $k => $v) {
            $userinfo['uid']=1;
            $userinfo['username']="系统";
            $userinfo['type']=\App\Lib\PublicInc::Type_System;
            $b =OrderReturnCreater::updateorder($v['refund_no'],$userinfo);
            if(!$b){
                LogApi::error("[cronBarterDelivey]换货确认收货失败");
            }

        }

    }


    /**
     * 定时任务 月初发送提前还款短信
     * 每个10秒发送50条数据
     * $return bool
     */
    public static function cronPrepayment(){

        try{
            $arr =[];
            $limit  = 50;
            $page   = 1;
            $sleep  = 10;

            do {
                $whereArray[] = ['order_info.order_status', '=', Inc\OrderStatus::OrderInService];
                $whereArray[] = ['term', '=', date('Ym')];

                // 查询总数
                $total =  \App\Order\Models\OrderGoodsInstalment::query()
                    ->where($whereArray)
                    ->whereIn('status',[Inc\OrderInstalmentStatus::UNPAID,Inc\OrderInstalmentStatus::FAIL])
                    ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                    ->count();
                $totalpage = ceil($total/$limit);

                // 查询数据
                $result =  \App\Order\Models\OrderGoodsInstalment::query()
                    ->select('order_goods_instalment.id')
                    ->where($whereArray)
                    ->whereIn('status',[Inc\OrderInstalmentStatus::UNPAID,Inc\OrderInstalmentStatus::FAIL])
                    ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                    ->forPage($page,$limit)
                    ->get()
                    ->toArray();

                if (!$result) {
                    continue;
                }

                foreach($result as $item){
                    //发送短信
                    $notice = new \App\Order\Modules\Service\OrderNotice(
                        \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                        $item['id'],
                        "CronRepayment");
                    $notice->notify();
                }

                $page++;
                sleep($sleep);
            } while ($page <= $totalpage);

            if(count($arr) > 0){
                LogApi::notify("提前还款短信", $arr);
            }

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[提前还款短信]', ['msg'=>$exc->getMessage()]);
        }
    }

    /**
     * 定时任务 提前一、三、七天发送扣款短信
     * 每个10秒发送50条数据
     * $return bool
     */
    public static function cronPrepaymentMessage($type){
        try{
            $limit  = 50;
            $page   = 1;
            $sleep  = 10;
            $dayArr = [ 1 => 'WithholdAdvanceOne', 3 => 'WithholdAdvanceThree', 7 => 'WithholdAdvanceSeven'];

            if(!isset($dayArr[$type])){
                \App\Lib\Common\LogApi::debug('[cronWithholdMessage提前还款短信]', ['msg'=>'参数错误']);
                return false;
            }

            $today  = date("Ymd", strtotime("+" . $type . " day"));

            $term   = substr($today,0,6);
            $year   = substr($today,0,4);
            $mouth  = substr($today,4,2);
            $day    = substr($today,6,2);

            $model  = $dayArr[$type];
            $createTime = $year . '年' . $mouth . '月' . $day . '日';

            // 订单在服务中 长租的订单分期
            $whereArray[] = ['order_info.order_status', '=', Inc\OrderStatus::OrderInService];
            $whereArray[] = ['order_info.zuqi_type', '=', Inc\OrderStatus::ZUQI_TYPE_MONTH];    //长租订单
            $whereArray[] = ['term', '=', $term];
            $whereArray[] = ['day', '=', intval($day)];
            // 查询总数
            $total =  \App\Order\Models\OrderGoodsInstalment::query()
                ->where($whereArray)
                ->whereIn('status',[Inc\OrderInstalmentStatus::UNPAID,Inc\OrderInstalmentStatus::FAIL])
                ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                ->count();

            \App\Lib\Common\LogApi::debug('[cronWithholdMessage:提前 ' . $type . '天还款 发送短信总数：' . $total . ']');

            $totalpage = ceil($total/$limit);

            do {
                // 查询数据
                $result =  \App\Order\Models\OrderGoodsInstalment::query()
                    ->select('order_goods_instalment.id')
                    ->where($whereArray)
                    ->whereIn('status',[Inc\OrderInstalmentStatus::UNPAID,Inc\OrderInstalmentStatus::FAIL])
                    ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                    ->forPage($page,$limit)
                    ->get()
                    ->toArray();
                if (!$result) {
                    break;
                }

                foreach($result as $item){
                    //发送短信
                    $notice = new \App\Order\Modules\Service\OrderNotice(
                        \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                        $item['id'],
                        $model,
                        ['createTime' => $createTime]
                    );
                    $notice->notify();
                }

                $page++;
                sleep($sleep);
            } while ($page <= $totalpage);

            \App\Lib\Common\LogApi::debug('[cronWithholdMessage:提前 ' . $type . '天还款 发送短信成功]');

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[cronPrepaymentMessage提前还款短信]', ['msg'=>$exc->getMessage()]);
        }
    }

    /**
     * 定时任务 提前一、三、七天发送扣款短信
     * 每个10秒发送50条数据
     * $return bool
     */
    public static function cronOverdueMessage($type){
        try{
            $limit  = 50;
            $page   = 1;
            $sleep  = 10;
            $dayArr = [ 1 => 'WithholdOverduOne', 3 => 'WithholdOverduThree'];
            if(!isset($dayArr[$type])){
                \App\Lib\Common\LogApi::debug('[cronOverdueMessage逾期短信]', ['msg'=>'参数错误']);
                return false;
            }

            $today  = date("Ymd", strtotime("-" . $type . " day"));

            $term   = substr($today,0,6);
            $year   = substr($today,0,4);
            $mouth  = substr($today,4,2);
            $day    = substr($today,6,2);

            $model  = $dayArr[$type];
            $createTime = $year . '年' . $mouth . '月' . $day . '日';

            // 订单在服务中 长租的订单分期
            $whereArray[] = ['order_info.order_status', '=', Inc\OrderStatus::OrderInService];
            $whereArray[] = ['order_info.zuqi_type', '=', Inc\OrderStatus::ZUQI_TYPE_MONTH];    //长租订单
            $whereArray[] = ['status', '=', Inc\OrderInstalmentStatus::FAIL];
            $whereArray[] = ['term', '=', $term];
            $whereArray[] = ['day', '=', intval($day)];

            // 查询总数
            $total =  \App\Order\Models\OrderGoodsInstalment::query()
                ->where($whereArray)
                ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                ->count();
            \App\Lib\Common\LogApi::debug('[cronOverdueMessage:逾期 ' . $type . '天扣款 发送短信总数：' . $total . ']');

            $totalpage = ceil($total/$limit);

            do {
                // 查询数据
                $result =  \App\Order\Models\OrderGoodsInstalment::query()
                    ->select('order_goods_instalment.id')
                    ->where($whereArray)
                    ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                    ->forPage($page,$limit)
                    ->get()
                    ->toArray();
                if (!$result) {
                    break;
                }

                foreach($result as $item){

                    //发送短信
                    $notice = new \App\Order\Modules\Service\OrderNotice(
                        \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                        $item['id'],
                        $model,
                        ['createTime' => $createTime]
                    );
                    $notice->notify();
                }

                $page++;
                sleep($sleep);
            } while ($page <= $totalpage);

            \App\Lib\Common\LogApi::debug('[cronOverdueMessage:逾期 ' . $type . '天扣款 发送短信成功]');

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[cronOverdueMessage提前还款短信]', ['msg'=>$exc->getMessage()]);
        }
    }
    /**
     * 定时任务  获取连续两个月，总共三个月未缴租金的逾期数据
     * $return bool
     */
    public static function cronOverdueDeductionMessage(){
        try{
            LogApi::debug('[cronOverdueDeductionMessage进入程序]');
            //获取逾期扣款表已存在的数据
            $getOverdueDeductionInfo = OrderOverdueDeductionRepository::getOverdueInfo();
            $overdueData = [];
            if( $getOverdueDeductionInfo ){
                foreach ($getOverdueDeductionInfo as $item){
                    $overdueData[] = (string)$item['order_no'];
                    //判断数组中是否含有有效数据
                    $status = "false";
                    if($item['status'] == Inc\OrderOverdueStatus::EFFECTIVE){
                        $status = "true";
                    }
                }
            }
            LogApi::debug('[cronOverdueDeductionMessage获取逾期扣款表已存在的数据]', ['data'=>$overdueData]);
            //获取连续两个月，总共三个月未缴租金的逾期数据
            $orderNoArray = \App\Order\Modules\Service\OrderGoodsInstalment::instalmentOverdue();
            LogApi::debug('[cronOverdueDeductionMessage获取连续两个月，总共三个月未缴租金的逾期数据]', ['data'=>$orderNoArray]);
            $data = [];
            if( $orderNoArray ){
                $getData=[];
                foreach($orderNoArray as $item){
                    $getData[] = (string)$item['order_no'];
                    //获取连续两个月，总共三个月未缴租金的逾期数据 不在表中，则添加逾期数据
                    if(!in_array((string)$item['order_no'],$overdueData,true)){
                        //获取订单信息
                        $orderInfo = OrderOverdueDeductionRepository::getOverdueOrderDetail((string)$item['order_no']);

                        //添加数据
                        if($orderInfo){
                            if( $orderInfo['surplus_yajin'] == 0){
                                $surplus_yajin = $orderInfo['yajin'];
                            }else{
                                $surplus_yajin = $orderInfo['surplus_yajin'];
                            }
                            $data = [
                                'order_no'        => (string)$item['order_no'],
                                'order_time'     => $orderInfo['create_time'],
                                'channel_id'     => $orderInfo['channel_id'],
                                'app_id'          => $orderInfo['appid'],
                                'goods_name'     => $orderInfo['goods_name'],
                                'zuqi_type'      => $orderInfo['zuqi_type'],
                                'user_name'      => empty($orderInfo['realname'])?'':$orderInfo['realname'],
                                'mobile'         => $orderInfo['mobile'],
                                'unpaid_amount' => $item['amount'],
                                'overdue_amount'=> $surplus_yajin,
                                'user_id'        => $orderInfo['user_id'],
                                'create_time'   => time(),
                                'status'         => Inc\OrderOverdueStatus::EFFECTIVE//有效状态


                            ];

                            $createResult = OrderOverdueDeductionRepository::createOverdue($data);//创建符合要求的数据
                            if( !$createResult){
                                LogApi::debug('[cronOverdueDeductionMessage创建符合要求的数据失败]');
                                return false;
                            }
                        }

                    }

                }
                LogApi::debug('[cronOverdueDeductionMessage获取连续两个月，总共三个月未缴租金的逾期数据转化为一维数组数据]',['data'=>$getData]);
                //如果逾期扣款表数据不在连续两个月，总共三个月未缴租金的逾期数据中，则更改状态为无效
                if( $getOverdueDeductionInfo ){
                    foreach ($getOverdueDeductionInfo as $item){
                        if(!in_array((string)$item['order_no'],$getData,true)){
                            //如果数据状态是有效，则更改为无效
                            if($item['status'] == Inc\OrderOverdueStatus::EFFECTIVE){
                                $data = ['status' => Inc\OrderOverdueStatus::INVALID];//无效状态
                                $upResult = OrderOverdueDeductionRepository::upOverdueStatus((string)$item['order_no'],$data);
                                if( !$upResult){
                                    LogApi::debug('[cronOverdueDeductionMessage更改逾期未缴金额失败]');
                                    return false;
                                }
                            }
                        }
                        //获取连续两个月，总共三个月未缴租金的逾期数据 在表中并且无效状态，则更新为有效
                        if(in_array((string)$item['order_no'],$getData,true) && $item['status'] == Inc\OrderOverdueStatus::INVALID){
                            $data = [
                                'status'         => Inc\OrderOverdueStatus::EFFECTIVE,//有效状态
                                'unpaid_amount' => self::getUnpaidAmount((string)$item['order_no'])//获取订单未缴金额总和
                            ];
                            $upResult = OrderOverdueDeductionRepository::upOverdueStatus((string)$item['order_no'],$data);
                            if( !$upResult){
                                LogApi::debug('[cronOverdueDeductionMessage更改逾期未缴金额失败]');
                                return false;
                            }
                        }
                        //获取连续两个月，总共三个月未缴租金的逾期数据 在表中未缴金额与获取未缴金额不一致，则修改金额
                        if(in_array((string)$item['order_no'],$getData,true) && $item['status'] == Inc\OrderOverdueStatus::EFFECTIVE && $item['unpaid_amount'] != self::getUnpaidAmount((string)$item['order_no'])){
                            $data = [
                                'unpaid_amount' => self::getUnpaidAmount((string)$item['order_no'])//获取订单未缴金额总和
                            ];
                            $upResult = OrderOverdueDeductionRepository::upOverdueStatus((string)$item['order_no'],$data);
                            if( !$upResult){
                                LogApi::debug('[cronOverdueDeductionMessage更改逾期未缴金额失败]');
                                return false;
                            }
                        }

                    }

                }
            }else{
                //获取逾期表中的有效数据，如果存在有效数据，全部更新为无效
                if( isset( $status ) && $status == "true"){
                    $data = ['status' => Inc\OrderOverdueStatus::INVALID];//无效状态
                    $upResult = \App\Order\Models\OrderOverdueDeduction::query()->update($data);
                    if( !$upResult){
                        LogApi::debug('[cronOverdueDeductionMessage更改为无效失败]');
                        return false;
                    }
                }
            }

        }catch (\Exception $exc){
            LogApi::debug('[cronOverdueDeductionMessage程序异常]', ['msg'=>$exc->getMessage()]);
        }

    }

    /**
     * /获取订单的剩余未缴租金
     * @param $order_no
     * @return mixed
     */
    public static function getUnpaidAmount($order_no){

        $orderNoArray = \App\Order\Modules\Service\OrderGoodsInstalment::instalmentOverdue();
        foreach ($orderNoArray as $item){
            if($item['order_no'] == $order_no){
                return $item['amount'];
            }
        }
    }

}