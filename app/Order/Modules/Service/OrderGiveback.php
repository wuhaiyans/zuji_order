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
		$data['status'] = OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY;
		$data['create_time'] = $data['update_time'] = time();
        return $this->order_giveback_repository->create( $data );
    }
}
