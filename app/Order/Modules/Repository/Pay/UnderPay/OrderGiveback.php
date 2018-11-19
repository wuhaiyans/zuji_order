<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Order\Modules\Service\OrderGiveback AS OG;


use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Repository\Order\Goods;
use App\Lib\ApiStatus;

/**
 * 还机业务处理【创建赔偿金=>线下支付】
 *
 */
class OrderGiveback implements UnderLine {


    /**
     * 订单编号
     */
    private $orderNo = '';
	
    /**
     * 设备编号
     */
    private $goodsNo = '';
	


    /**
	 * 
	 * @param array $params 查询需要的参数
	 * $params = [
	 *     'order_no' => '',//订单编号
	 *     'goods_no' => '',//设备编号
	 * ]
	 */
    public function __construct( $params ) {
		if( !isset($params['order_no']) || !$params['order_no'] ){
			\App\Lib\Common\LogApi::error('huanji-xianxiazhifu还机-线下支付：订单编号为空',[$params]);
			throw new \Exception('查询出错【订单编号为空】');
		}
		if( !isset($params['goods_no']) || !$params['goods_no'] ){
			\App\Lib\Common\LogApi::error('huanji-xianxiazhifu还机-线下支付：设备编号为空',[$params]);
			throw new \Exception('查询出错【设备编号为空】');
		}
        $this->orderNo = $params['order_no'];
        $this->goodsNo = $params['goods_no'];
    }



    /**
     * 计算该付款金额
     * return string
     */
    public function getPayAmount(){
		$orderGiveback = new OG();
		$givebackInfo = $orderGiveback->getInfoByGoodsNo($this->goodsNo);
		if( !$givebackInfo ){
			\App\Lib\Common\LogApi::error('huanji-xianxiazhifu还机-线下支付：还机信息为空',['goodsNo'=>$this->goodsNo,'givebackInfo'=>$givebackInfo]);
			throw new \Exception('查询出错【当前设备还机信息为空】');
		}
		return $givebackInfo['instalment_amount']+$givebackInfo['compensate_amount'];
    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute( ){
		try{
			//查询订单信息
			$orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $this->orderNo );
			if( !$orderInfo ){
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]订单单信息获取失败', ['$this->orderNo'=>$this->orderNo,'$orderInfo'=>$orderInfo]);
				return false;
			}
			$order_type = $orderInfo['order_type'];
			
			$orderGivebackService = new OG();
			//获取还机单信息
			$orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($this->goodsNo);
			if( !$orderGivebackInfo ) {
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]还机单信息获取失败', ['$this->goodsNo'=>$this->goodsNo,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//还机单不处于待支付的订单不允许线下还机
			if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_PAY ){
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]还机单不处于待支付', ['$this->goodsNo'=>$this->goodsNo,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//创建服务层对象
			$orderGoods = Goods::getByGoodsNo($orderGivebackInfo['goods_no']);
			if( !$orderGoods ){
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]商品服务层创建失败', ['$orderGoods'=>$orderGoods,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//获取商品信息
			$orderGoodsInfo = $orderGoods->getData();
			if( !$orderGoodsInfo ) {
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]商品信息获取失败', ['$orderGoodsInfo'=>$orderGoodsInfo,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			
			//-+--------------------------------------------------------------------
			// | 判断支付单是否存在未完成分期的期数，存在则将关闭的分期单转为支付成功
			// | 添加时间：2018-08-28 12:06:35 【协调人：吴天堂、王昌俊】
			//-+--------------------------------------------------------------------
			$instalmentResult = true;//初始化分期状态扭转结果
			if( $orderGivebackInfo['instalment_num'] ){
				$instalmentResult = \App\Order\Modules\Repository\Order\Instalment::instalmentStatusSuccess(['goods_no'=>$orderGivebackInfo['goods_no']]);
			}
			if( !$instalmentResult ){
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]分期状态扭转失败', ['$orderGoodsInfo'=>$orderGoodsInfo,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			
			
			//-+--------------------------------------------------------------------
			// | 判断订单押金，是否生成清算单
			//-+--------------------------------------------------------------------

			//-+--------------------------------------------------------------------
			// | 不生成=》更新订单状态（交易完成）
			//-+--------------------------------------------------------------------
			if( $orderGoodsInfo['yajin'] == 0 ){
				//更新商品状态
				$orderGoodsResult = $orderGoods->givebackFinish();
				if(!$orderGoodsResult){
					\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]更新商品表状态失败', ['$orderGoodsResult'=>$orderGoodsResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
					return false;
				}
				//解冻订单
				if(!OrderGiveback::__unfreeze($orderGoodsInfo['order_no'])){
					\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]订单解冻失败', ['$orderGivebackInfo'=>$orderGivebackInfo]);
					return false;
				}
				$status = OrderGivebackStatus::STATUS_DEAL_DONE;
				$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$orderGivebackInfo['giveback_no']], [
					'status'=> $status,
					'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN,
					'payment_status'=> OrderGivebackStatus::PAYMENT_STATUS_ALREADY_PAY,
					'payment_time'=> time(),
				]);
				
				
				//需要记录清算，清算数据为空即可
				$paymentNo = $fundauthNo = '';
			}
			//-+--------------------------------------------------------------------
			// | 生成=>更新订单状态（处理中，待清算）
			//-+--------------------------------------------------------------------
			else{
				$status = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
				$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$orderGivebackInfo['giveback_no']], [
					'status'=> $status,
					'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_IN_RETURN,
					'payment_status'=> OrderGivebackStatus::PAYMENT_STATUS_ALREADY_PAY,
					'payment_time'=> time(),
				]);
				if( $order_type != \App\Order\Modules\Inc\OrderStatus::orderMiniService ){
				//获取当时订单支付时的相关pay的对象信息[查询payment_no和funath_no]
				$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,$orderGoodsInfo['order_no'] );
				$paymentNo = $payObj->getPaymentNo();
				$fundauthNo = $payObj->getFundauthNo();
				}else{
					//更新商品状态
					$orderGoodsResult = $orderGoods->givebackFinish();
					if(!$orderGoodsResult){
						\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]更新商品表状态失败', ['$orderGoodsResult'=>$orderGoodsResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
						return false;
					}
					//解冻订单
					if(!OrderGiveback::__unfreeze($orderGoodsInfo['order_no'])){
						\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]订单解冻失败', ['$orderGivebackInfo'=>$orderGivebackInfo]);
						return false;
					}
					$status = OrderGivebackStatus::STATUS_DEAL_DONE;
					$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$orderGivebackInfo['giveback_no']], [
						'status'=> $status,
						'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN,
						'payment_status'=> OrderGivebackStatus::PAYMENT_STATUS_ALREADY_PAY,
						'payment_time'=> time(),
					]);
					$paymentNo = '';
					$fundauthNo = '';
				}
			}
			if( !$orderGivebackResult ){
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]还机单状态更新失败', ['$orderGivebackResult'=>$orderGivebackResult,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			//清算处理数据拼接
			$clearData = [
				'user_id' => $orderGivebackInfo['user_id'],
				'order_no' => $orderGivebackInfo['order_no'],
				'business_type' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
				'business_no' => $orderGivebackInfo['giveback_no'],
				'auth_deduction_amount' => 0,//扣除押金金额
				'auth_unfreeze_amount' => $orderGoodsInfo['yajin'],//退还押金金额
				'out_payment_no' => $paymentNo,//payment_no
				'out_auth_no' => $fundauthNo,//和funath_no
			];
			//判断是否为小程序（小程序清算数据为已完成）
			if($order_type){
				if($order_type == \App\Order\Modules\Inc\OrderStatus::orderMiniService){
					$clearData['status'] = OrderCleaningStatus::orderCleaningComplete;//清算单状态为已完成
				}
			}
			//进入清算处理
			$orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
			if( !$orderCleanResult ){
				set_apistatus(ApiStatus::CODE_93200, '押金退还清算单创建失败!');
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]押金退还清算单创建失败', ['$orderCleanResult'=>$orderCleanResult,'$clearData'=>$clearData,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}
			
			//记录日志
			$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
				'order_no'=>$orderGivebackInfo['order_no'],
				'action'=>'还机单线下支付',
				'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
				'business_no'=>$orderGivebackInfo['giveback_no'],
				'goods_no'=>$orderGivebackInfo['goods_no'],
				'operator_id'=>0,
				'operator_name'=>'后台手动添加赔偿金',
				'operator_type'=>\App\Lib\PublicInc::Type_System,//此处用常量
				'msg'=>'还机单相关支付完成',
			]);
			if( !$goodsLog ){
				set_apistatus(ApiStatus::CODE_92700, '设备日志记录失败!');
				\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]设备日志记录失败', ['$goodsLog'=>$goodsLog,'$orderGivebackInfo'=>$orderGivebackInfo]);
				return false;
			}

//			//发送短信
//			$notice = new \App\Order\Modules\Service\OrderNotice(
//				\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
//				$orderGivebackInfo['giveback_no'],
//				"GivebackPayment");
//			$notice->notify();

		} catch (\Exception $ex) {
			\App\Lib\Common\LogApi::debug('还机单线下支付[huanji-xianxiazhifu]异常', $ex);
			return false;
		}
		return true;

    }

}
