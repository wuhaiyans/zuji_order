<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGiveback;

class OrderGivebackRepository 
{
	/**
	 * 订单还机单model类
	 * @var obj OrderGiveback
	 */
	protected $order_giveback_model;
	protected $fillable;
	
	/**
	 * 构造方法
	 */
	public function __construct(  ){
		$this->order_giveback_model = new OrderGiveback();
		$this->fillable = $this->order_giveback_model->fillable;
	}
    public function create( $data ){
		return $this->order_giveback_model->insertGetId( $data );
	}
    /**
     * 根据商品编号获取一条还机单数据
	 * @param string $goodsNo 商品编号
	 * @return array 
	 */
	public function getInfoByGoodsNo( $goodsNo ) {
		$where['goods_no'] = $goodsNo;
		$result = $this->order_giveback_model->fillable($this->fillable)->where($where)->first();
		if( $result ) {
			return $result->toArray();
		}
		set_apistatus(\App\Lib\ApiStatus::CODE_92400, '获取还机单数据为空!');
		return false;
	}
    /**
     * 获取当前订单下所有未完成的还机单
	 * @param string $orderNo 订单编号
	 * @return array|false
	 */
	public function getUnfinishedListByOrderNo( $orderNo ) {
		$where['order_no'] = $orderNo;
		$where['status'] = [
			\App\Order\Modules\Inc\OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY,
			\App\Order\Modules\Inc\OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK,
			\App\Order\Modules\Inc\OrderGivebackStatus::STATUS_DEAL_WAIT_PAY,
			\App\Order\Modules\Inc\OrderGivebackStatus::STATUS_DEAL_IN_PAY,
			\App\Order\Modules\Inc\OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI,
		];
		$result = $this->order_giveback_model->fillable($this->fillable)->where($where)->get();
		if( $result ) {
			return $result->toArray();
		}
		set_apistatus(\App\Lib\ApiStatus::CODE_92400, '获取还机单数据为空!');
		return false;
	}
	/**
	 * 根据条件更新数据
	 * @param array $where
	 * @param array $data
	 * @return boolen
	 */
	public function update( $where, $data ) {
		return $this->order_giveback_model->where($where)->update($data);
	}
}