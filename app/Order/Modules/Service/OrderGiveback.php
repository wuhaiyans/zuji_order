<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderGivebackRepository;
use App\Order\Modules\Inc\OrderGivebackStatus;

class OrderGiveback
{
	/**
	 * 订单还机数据处理仓库
	 * @var obj
	 */
	protected $order_giveback_repository;
	public function __construct(  ) {
		$this->order_giveback_repository = new OrderGivebackRepository();
	}
    /**
     * 保存还机单数据
     * @param $data
     * @return id
     */
    public function create($data){
		$data = filter_array($data, [
			'order_no' => 'required',//订单编号
			'goods_no' => 'required',//商品编号
			'user_id' => 'required',//用户id
			'logistics_no' => 'required',//物流单号
		]);

		if( count($data)!=4 ){
			set_error('订单还机单存储失败：参数缺失!');
			return false;
		}
		$data['giveback_no'] = createNo(7);
		$data['status'] = OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY;
		$data['create_time'] = $data['update_time'] = time();
        return $this->order_giveback_repository->create( $data );
    }
    /**
     * 根据商品编号获取一条还机单数据
	 * @param string $goodsNo 商品编号
	 * @return array 
	 */
	public function getInfoByGoodsNo( $goodsNo ) {
		if( empty($goodsNo) ) {
			return [];
		}
		return $this->order_giveback_repository->getInfoByGoodsNo($goodsNo);
	}
	
    /**
     * 根据条件更新数据
	 * @param array $where 更新条件【至少含有一项条件】
	 * $where = [<br/>
	 *		'goods_no' => '',//商品编号<br/>
	 * ]<br/>
	 * @param array $data 需要更新的数据 【至少含有一项数据】
	 * $data = [<br/>
	 *		'status'=>'',//还机状态<br/>
	 * ]
	 */
	public function update( $where, $data ) {
		$where = filter_array($where, [
			'goods_no' => 'required',
		]);
		$data = filter_array($data, [
			'status' => 'required',
		]);
		if( count( $where ) < 1 ){
			return false;
		}
		if( count( $data ) < 1 ){
			return false;
		}
		$data['update_time'] = time();
		return $this->order_giveback_repository->update( $where, $data );
	}
}
