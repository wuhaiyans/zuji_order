<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderGoodsRepository;

class OrderGoods
{
	/**
	 * 订单商品数据处理仓库
	 * @var obj
	 */
	protected $orderGoodsRepository;
	public function __construct(  ) {
		$this->orderGoodsRepository = new OrderGoodsRepository(new \App\Order\Models\OrderGoods());
	}
	/**
	 * 获取一条商品信息
	 * @param string $goodsNo 商品编号
	 * @return array $goodsInfo 商品基础信息|空<br/>
	 * $goodsInfo = [<br/>
	 *		'id' => '',//订单商品自增id<br/>
	 *		'goods_name' => '',//订单商品自增id<br/>
	 *		'zuji_goods_id' => '',//以前系统商品id<br/>
	 *		'goods_no' => '',//商品sn<br/>
	 *		'goods_thumb' => '',//缩略图<br/>
	 *		'prod_id' => '',//产品id<br/>
	 *		'prod_no' => '',//产品sn<br/>
	 *		'brand_id' => '',//品牌id<br/>
	 *		'category_id' => '',//分类id<br/>
	 *		'user_id' => '',//用户id<br/>
	 *		'quantity' => '',//商品数量<br/>
	 *		'goods_yajin' => '',//商品押金<br/>
	 *		'yajin' => '',//实际押金<br/>
	 *		'zuqi' => '',//租期<br/>
	 *		'zuqi_type' => '',//租期类型 1.天 2月<br/>
	 *		'zujin' => '',//月租金<br/>
	 *		'order_no' => '',//订单编号<br/>
	 *		'chengse' => '',//成色<br/>
	 *		'discount_amount' => '',//优惠金额（商品券）<br/>
	 *		'coupon_amount' => '',//优惠券金额（现金券，首月0租金）<br/>
	 *		'amount_after_discount' => '',//优惠后的总租金（总租金（zujin*zuqi） - 优惠金额-优惠券金额（现金券金额））<br/>
	 *		'edition' => '',//商品编辑的版本号<br/>
	 *		'business_key' => '',//业务类型<br/>
	 *		'business_no' => '',//业务编号<br/>
	 *		'market_price' => '',//租机市价<br/>
	 *		'price' => '',//实际支付总金额（优惠后金额+意外险+押金）<br/>
	 *		'specs' => '',//商品价格属性<br/>
	 *		'insurance' => '',//商品意外险<br/>
	 *		'buyout_price' => '',//买断价格<br/>
	 *		'begin_time' => '',//服务开始时间<br/>
	 *		'end_time' => '',//服务结束时间<br/>
	 *		'weight' => '',//商品重量，计算物流价格用<br/>
	 *		'goods_status' => '',//1提交申请，2同意，3审核拒绝<br/>
	 *		'create_time' => '',//创建时间<br/>
	 *		'update_time' => '',//更新时间<br/>
	 * ]
	 */
	public function getGoodsInfo( $goodsNo ) {
		//判断参数
		if( empty( $goodsNo ) ) {
			get_instance()->setCode(\App\Lib\ApiStatus::CODE_92300)->setMsg('获取商品信息时商品编号参数缺失');
			return [];
		}
		return $this->orderGoodsRepository->getGoodsInfo( $goodsNo );
	}
    /**
     * 根据条件更新数据
	 * @param array $where 更新条件【至少含有一项条件】
	 * $where = [<br/>
	 *		'goods_no' => '',//商品编号<br/>
	 * ]<br/>
	 * @param array $data 需要更新的数据 【至少含有一项数据】
	 * $data = [<br/>
	 *		'bussiness_key'=>'',//业务key<br/>
	 *		'bussiness_no'=>'',//业务唯一编号<br/>
	 *		'goods_status'=>'',//业务key下的商品状态<br/>
	 * ]
	 */
	public function update( $where, $data ) {
		$where = filter_array($where, [
			'goods_no' => 'required',
		]);
		$data = filter_array($data, [
			'bussiness_key' => 'required',
			'bussiness_no' => 'required',
			'goods_status' => 'required',
		]);
		if( count( $where ) < 1 ){
			set_apistatus('', '');
			return false;
		}
		if( count( $data ) < 1 ){
			return false;
		}
		$data['update_time'] = time();
		return $this->orderGoodsRepository->update( $where, $data );
	}
}
