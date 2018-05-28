<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderBuyout;
use App\Order\Modules\Inc\OrderBuyoutStatus;
/**
 * 订单买断单数据处理仓库
 * @var obj OrderBuyoutRepository
 * @author limin<limin@huishoubao.com.cn>
 */
class OrderBuyoutRepository
{
	/**
	 * 查询单条买断单数据
	 * @param array $where 【必选】 查询条件
	 * [
	 * 		"id" =>"", 主键id
	 * 		"order_no" =>"", 订单编号
	 * 		"goods_no" =>"", 商品编号
	 * ]
	 * @return array|bool
	 */
	public static function getInfo(array $where){
		$orderBuyoutRow =  OrderBuyout::query()->where($where)->first();
		if (!$orderBuyoutRow) return false;
		return $orderBuyoutRow->toArray();
	}

	/**
	 * 创建买断单数据
	 * @param array $data 【必选】 插入内容
	 * @return int|bool
	 */
	public static function create(array $data){
		return OrderBuyout::insertGetId($data);
	}

	/**
	 * 更新买断状态为已取消
	 * @param int $id 【必选】 主键id
	 * @param int $platId 【必选】 操作人id
	 * @return int|bool
	 */
	public static function setOrderBuyoutCancel($id,$platId){
		if (!$id) return false;
		$data =[
				'plat_id'=>$platId,
				'status'=>OrderBuyoutStatus::OrderCancel,
				'update_time'=>time()
		];
		$ret = OrderBuyout::where('id', '=', $id)->update($data);
		if($ret){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 更新买断状态为已支付
	 * @param int $id 【必选】 主键id
	 * @return int|bool
	 */
	public static function setOrderPaid($id){
		if (!$id) return false;
		$data =[
			'status'=>OrderBuyoutStatus::OrderPaid,
			'update_time'=>time()
		];
		$ret = Order::where('id', '=', $id)->update($data);
		if($ret){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 更新买断状态为已解押
	 * @param int $id 【必选】 主键id
	 * @return int|bool
	 */
	public static function setOrderRelease($id){
		if (!$id) return false;
		$data =[
				'status'=>OrderBuyoutStatus::OrderRelease,
				'update_time'=>time()
		];
		$ret = Order::where('id', '=', $id)->update($data);
		if($ret){
			return true;
		}else{
			return false;
		}
	}
}