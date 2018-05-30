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
	/**
	 * 查询单条买断单
	 * @param $data
	 * @return id
	 */
	public static function getInfo($goodsNo,$userId=0){
		if(!$goodsNo){
			return false;
		}
		if($userId>0){
			$where['user_id'] = $userId;
		}
		$where['goods_no'] = $goodsNo;

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
	/**
	 * 买断已支付
	 * @param int $id 买断单主键id
	 * @param int $userId 操作人id
	 * @return id
	 */
	public static function paid($id,$userId){
		$id = intval($id);
		$userId = intval($userId);
		if(!$id || $userId){
			return false;
		}
		return OrderBuyoutRepository::setOrderPaid($id,$userId);
	}
	/**
	 * 买断完成
	 * @param int $id 买断单主键id
	 * @param int $userId 操作人id
	 * @return id
	 */
	public static function over($id,$userId){
		$id = intval($id);
		$userId = intval($userId);
		if(!$id || $userId){
			return false;
		}
		return OrderBuyoutRepository::setOrderRelease($id,$userId);
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
}
