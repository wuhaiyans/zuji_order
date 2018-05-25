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
			'logistics_id' => 'required',//物流类型
			'logistics_name' => 'required',//物流名称
			'logistics_no' => 'required',//物流单号
			'giveback_no' => 'required',//还机单编号
			'status' => 'required',//订单状态
		]);

		if( count($data)!=8 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92100, '还机单创建：必要参数缺失!');
			return false;
		}
		$data['create_time'] = $data['update_time'] = time();
        $result = $this->order_giveback_repository->create( $data );
		if( !$result ) {
			set_code(\App\Lib\ApiStatus::CODE_92201);
		}
		return $result;
    }
    /**
     * 根据商品编号获取一条还机单数据
	 * @param string $goodsNo 商品编号
	 * @return array|false
	 */
	public function getInfoByGoodsNo( $goodsNo ) {
		if( empty($goodsNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取还机单数据时订单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getInfoByGoodsNo($goodsNo);
	}
    /**
     * 获取当前订单下所有未完成的还机单
	 * @param string $orderNo 订单编号
	 * @return array|false
	 */
	public function getUnfinishedListByOrderNo( $orderNo ) {
		if( empty($orderNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取未完成的还机单列表：订单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getUnfinishedListByOrderNo( $orderNo );
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
	 *		'withhold_status'=>'',//代扣状态<br/>
	 *		'instalment_num'=>'',//剩余还款的分期数<br/>
	 *		'instalment_amount'=>'',//剩余还款的分期总金额（分）<br/>
	 *		'payment_status'=>'',//支付状态 0默认<br/>
	 *		'payment_time'=>'',//支付时间<br/>
	 *		'logistics_id'=>'',//物流类型<br/>
	 *		'logistics_name'=>'',//物流名称<br/>
	 *		'logistics_no'=>'',//物流编号<br/>
	 *		'evaluation_status'=>'',//检测结果<br/>
	 *		'evaluation_remark'=>'',//检测备注<br/>
	 *		'evaluation_time'=>'',//检测时间<br/>
	 *		'yajin_status'=>'',//押金退还状态<br/>
	 *		'compensate_amount'=>'',//赔偿金额<br/>
	 *		'remark'=>'',//备注<br/>
	 * ]
	 */
	public function update( $where, $data ) {
		$where = filter_array($where, [
			'goods_no' => 'required',
		]);
		$data = filter_array($data, [
			'status' => 'required',
			'withhold_status' => 'required',
			'instalment_num' => 'required',
			'instalment_amount' => 'required',
			'payment_status' => 'required',
			'payment_time' => 'required',
			'logistics_id' => 'required',
			'logistics_name' => 'required',
			'logistics_no' => 'required',
			'evaluation_status' => 'required',
			'evaluation_remark' => 'required',
			'evaluation_time' => 'required',
			'compensate_amount' => 'required',
			'remark' => 'required',
		]);
		if( count( $where ) < 1 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92600,'还机单修改：条件参数为空');
			return false;
		}
		if( count( $data ) < 1 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92600,'还机单修改：数据参数为空');
			return false;
		}
		$data['update_time'] = time();
		return $this->order_giveback_repository->update( $where, $data );
	}
	/**
	 * 还机单清算完成回调接口
	 * @param array $params 还机单清算完成回调参数<br/>
	 * $params = [
	 *		'business_type' => '',//业务类型【必须是还机业务】
	 *		'business_no' => '',//业务编码【必须是还机单编码】
	 *		'status' => '',//支付状态  processing：处理中；success：支付完成
	 * ]
	 */
	public static function callbackClearing( $params ) {
		//清算成功
		//-+--------------------------------------------------------------------
		// | 更新订单状态（交易完成）
		//-+--------------------------------------------------------------------
		//清算失败
		//-+--------------------------------------------------------------------
		// | 更新订单状态（交易完成，清算失败）
		//-+--------------------------------------------------------------------
	}
	
	/**
	 * 还机单支付完成回调接口
	 * @param Request $request
	 */
	public function callbackPayment( $params ) {
		//-+--------------------------------------------------------------------
		// | 判断是否支付成功
		//-+--------------------------------------------------------------------
		//支付成功
		
		//-+--------------------------------------------------------------------
		// | 判断订单押金，是否生成清算单
		//-+--------------------------------------------------------------------
		//不生成
		//-+--------------------------------------------------------------------
		// | 更新订单状态（交易完成）
		//-+--------------------------------------------------------------------
		//生成
		//-+--------------------------------------------------------------------
		// | 更新订单状态（处理中，待清算）
		//-+--------------------------------------------------------------------
	}
}
