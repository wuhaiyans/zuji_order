<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;

class OrderGoodsRepository
{

    private $orderGoods;

    public function __construct(OrderGoods $orderGoods)
    {
        $this->orderGoods = $orderGoods;
    }
    public function create(){

        var_dump('创建商品信息');
    }
    //获取商品信息
    public static function getgoodsList($goods_no){
        if (empty($goods_no)) return false;
        $result =  orderGoods::query()->where([
            ['goods_no', '=', $goods_no],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }
	/**
	 * 根据商品编号获取单条商品信息
	 * @param string $goodsNo 商品编号
	 * @return array $goodsInfo 商品基础信息|空<br/>
	 * $goodsInfo = [<br/>
	 *		'id' => '',//订单商品自增id<br/>
	 *		'goods_name' => '',//订单商品自增id<br/>
	 *		'zuji_goods_id' => '',//以前商品id<br/>
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
        $result =  $this->orderGoods->where(['goods_no'=> $goodsNo])->first();
        if (!$result) {
			return [];
		}
        $goodsInfo = $result->toArray();
		$goodsInfo['update_time'] = date('Y-m-d H:i:s',$goodsInfo['update_time']);
		$goodsInfo['create_time'] = date('Y-m-d H:i:s',$goodsInfo['create_time']);
		$goodsInfo['begin_time'] = date('Y-m-d H:i:s',$goodsInfo['begin_time']);
		$goodsInfo['end_time'] = date('Y-m-d H:i:s',$goodsInfo['end_time']);
		return $goodsInfo;
	}
}