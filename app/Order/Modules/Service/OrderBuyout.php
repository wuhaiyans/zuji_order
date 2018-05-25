<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderBuyoutRepository;
use App\Order\Modules\Inc\OrderBuyoutStatus;

class OrderBuyout
{
	/**
	 * 订单还机数据处理仓库
	 * @var obj
	 */
	public function __construct(  ) {
	}
	public static function getInfo($goodsNo){
		if(!$goodsNo){
			return false;
		}
		return OrderBuyoutRepository::getInfo(['goods_no'=>$goodsNo]);
	}
    /**
     * 创建买断单
     * @param $data
     * @return id
     */
    public static function create($array)
	{
		$data = filter_array($array,[
				'order_no'=>'required',
				'goods_no'=>'required',
				'user_id'=>'required',
				'buyout_price'=>'required',
		]);
		if(count($data)!=4){
			return false;
		}
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
		if(!$id || $userId){
			return false;
		}
		return OrderBuyoutRepository::setOrderBuyoutCancel($id,$userId);
	}
}
