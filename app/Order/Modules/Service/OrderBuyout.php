<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderBuyoutRepository;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;

class OrderBuyout
{
	/**
 * 订单还机数据处理仓库
 * @var obj
 */
	public function __construct(  ) {
	}


	/** 查询条件过滤
	 * @param array $where	【可选】查询条件
	 * [
	 *      'order_no' => '',	//【可选】订单号
	 *      'goods_name' => '',	//【可选】string；商品名称
	 *      'user_mobile'=>''      //【可选】int；用户手机号
	 * 		status'=>''      		//【可选】int；买断单状态
	 *      'begin_time'=>''      //【可选】int；开始时间
	 *      'end_time'=>''      //【可选】int；  截止时间
	 * ]
	 * @return array	查询条件
	 */
	public static function _where_filter($params){
		if ($params['begin_time']||$params['end_time']) {
			$begin_time = $params['begin_time']?strtotime($params['begin_time']):strtotime(date("Y-m-d",time()));
			$end_time = $params['end_time']?strtotime($params['end_time']):time();
			$where[] = ['order_buyout.create_time', '>=', $begin_time];
			$where[] = ['order_buyout.create_time', '<=', $end_time];
		}

		if($params['order_no']){
			$where[] = ['order_buyout.order_no', '=', $params['order_no']];
		}
		// order_no 订单编号查询，使用前缀模糊查询
		if($params['goods_name']){
			$where[] = ['order_goods.goods_name', '=', $params['goods_name']];
		}
		if($params['user_mobile']){
			$where[] = ['order_userinfo.user_mobile', '=', $params['user_mobile']];
		}
		if($params['status']){
			$where[] = ['order_buyout.status', '=', $params['status']];
		}
		if ($params['appid']) {
			$where[] =  ['order_info.appid', '=', $params['appid']];
		}
		return $where;
	}

	/**
	 * 查询单条买断单
	 * @param $data
	 * @return id
	 */
	public static function getInfo($buyout_no,$userId=0){
		if(!$buyout_no){
			return false;
		}
		if($userId>0){
			$where['user_id'] = $userId;
		}
		$where['buyout_no'] = $buyout_no;

		return OrderBuyoutRepository::getInfo($where);
	}
	/**
	 * 查询统计数量
	 * @param $data
	 * @return int
	 */
	public static function getCount($where){
		if(!$where){
			return false;
		}
		$where = self::_where_filter($where);
		$result = OrderBuyoutRepository::getCount($where);
		return $result;
	}
	/**
	 * 查询多条买断单
	 * @param $data
	 * @return id
	 */
	public static function getList($params){
		$additional['page'] = $params['page']?$params['page']:0;
		$additional['limit'] = $params['limit']?$params['limit']:0;
		$where = self::_where_filter($params);
		$data = OrderBuyoutRepository::getList($where, $additional);
		foreach($data['data'] as $k=>$v){
			$data['data'][$k]->c_time=date('Y-m-d H:i:s',$data['data'][$k]->c_time);
			$data['data'][$k]->create_time=date('Y-m-d H:i:s',$data['data'][$k]->create_time);
			$data['data'][$k]->complete_time=date('Y-m-d H:i:s',$data['data'][$k]->complete_time);
			$data['data'][$k]->wuliu_channel_name=Logistics::info($data['data'][$k]->wuliu_channel_id);//物流渠道
		}
		return $data;
	}
    /**
     * 创建买断单
     * @param $data
     * @return id
     */
    public static function create($array)
	{
		$data = filter_array($array,[
				'buyout_no'=>'required',
				'order_no'=>'required',
				'goods_no'=>'required',
				'user_id'=>'required',
				'plat_id'=>'required',
				'buyout_price'=>'required',
				'create_time'=>'required',
		]);
		return OrderBuyoutRepository::create($data);
	}
	/**
	 * 取消买断单
	 * @param int $id 买断单主键id
	 * @param int $userId 操作人id
	 * @return id
	 */
	public static function cancel($id,$userId){
		$id = intval($id);
		$userId = intval($userId);
		if(!$id || !$userId){
			return false;
		}
		return OrderBuyoutRepository::setOrderBuyoutCancel($id,$userId);
	}

	/*
     * 支付完成
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "goods_no"=>"",商品编号
     * ]
     * @return json
     */
	public static function callbackPaid($params){
		//过滤参数
		$rule = [
				'business_type'     => 'required',//业务类型
				'business_no'     => 'required',//业务编码
				'status'     => 'required',//支付状态
		];
		$validator = app('validator')->make($params, $rule);
		if ($validator->fails()) {
			return false;
		}
		if( $params['status'] != 'success' || $params['business_type'] != \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT ){
			return false;
		}
		//获取买断单
		$buyout = OrderBuyout::getInfo($params['business_no']);
		if(!$buyout){
			echo 2;die;
			return false;
		}
		if($buyout['status']==OrderBuyoutStatus::OrderPaid){
			echo 3;die;
			return false;
		}
		$data = [
				'order_no'=>$buyout['order_no'],
				'goods_no'=>$buyout['goods_no'],
		];
		$ret = \App\Order\Modules\Repository\OrderInstalmentRepository::closeInstalment($data);
		if(!$ret){
			//return false;
		}
		//更新买断单
		$ret = OrderBuyoutRepository::setOrderPaid($buyout['id'],$buyout['user_id']);
		if(!$ret){
			echo 4;die;
			return false;
		}
		return true;
	}
	/*
     * 买断完成
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "goods_no"=>"",商品编号
     * ]
     * @return json
     */
	public static function callbackOver($params){
		//过滤参数
		$rule = [
				'buyout_no'=>'required',
				'user_id'=>'required',
		];
		$validator = app('validator')->make($params, $rule);
		if ($validator->fails()) {
			return false;
		}
		//获取买断单
		$buyout = OrderBuyout::getInfo($params['buyout_no'],$params['user_id']);
		if(!$buyout){
			return false;
		}
		if($buyout['status']==OrderBuyoutStatus::OrderRelease){
			return false;
		}
		//获取订单商品信息
		$OrderGoodsRepository = new OrderGoodsRepository;
		$goodsInfo = $OrderGoodsRepository->getGoodsInfo($params['goods_no']);
		if(empty($goodsInfo)){
			return false;
		}
		//解冻订单
		$OrderRepository= new OrderRepository;
		$ret = $OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Non);
		if(!$ret){
			return false;
		}
		//更新订单商品
		$goods = [
				'goods_status' => OrderGoodStatus::BUY_OUT,
				'business_no' => $buyout['buyout_no'],
		];
		$ret = $OrderGoodsRepository->update(['id'=>$goodsInfo['id']],$goods);
		if(!$ret){
			return false;
		}
		//更新买断单
		$ret = OrderBuyoutRepository::setOrderRelease($buyout['id'],$params['user_id']);
		if(!$ret){
			return false;
		}
		return true;
	}

}
