<?php
namespace oms\order_creater;

use oms\OrderCreater;
use zuji\Config;
use zuji\debug\Debug;
use zuji\debug\Location;
/**
 * 订单分期组件
 *	使用代扣类型支付方式
 * @author limin <limin@huishoubao.com.cn>
 */
class InstalmentComponnet implements OrderCreaterComponnet {
    
    private $flag = true;
    private $componnet = null;
	private $schema = null;

	//租期
	private $zuqi = 0;
	//租期类型
	private $zuqi_type = 0;
	//代扣协议号
	private $withholding_no = null;
	//订单原始金额
	private $all_amount = 0;
	//订单实际金额
	private $amount = 0;
	//租金
	private $zujin = 0;
	//优惠金额
	private $discount_amount = 0;
	//优惠方式
	private $coupon_type = 0;
	//意外险
	private $yiwaixian = 0;
	//首期金额
	private $first_amount = 0;
	//分期金额
	private $fenqi_amount = 0;
	//支付方式
	private $payment_type_id = 0;

    public function __construct(OrderCreaterComponnet $componnet) {
        $this->componnet = $componnet;
    }

	public function get_order_creater(): OrderCreater {
		return $this->componnet->get_order_creater();
	}

	public function filter():bool {
		//统一过滤
		$filter_b =  $this->componnet->filter();
		return $this->flag && $filter_b;
    }

	public function get_data_schema(): array{

		$this->schema = $this->componnet->get_data_schema();
		$this->zuqi = $this->schema['sku']['zuqi'];
		$this->zuqi_type = $this->schema['sku']['zuqi_type'];
		$this->withholding_no = $this->schema['user']['withholding_no'];
		$this->all_amount =  $this->schema['sku']['all_amount'];
		$this->amount = $this->schema['sku']['amount'];
		$this->zujin = $this->schema['sku']['zujin'];
		$this->discount_amount = $this->schema['coupon']['discount_amount'];
		$this->coupon_type = $this->schema['coupon']['coupon_type'];
		$this->yiwaixian = $this->schema['sku']['yiwaixian'];
		$this->fenqi_amount = $this->schema['sku']['zujin'];
		$this->first_amount = $this->zujin+$this->yiwaixian;
		$this->payment_type_id = $this->schema['sku']['payment_type_id'];
		// 2018-04-14
		// 如果租期类型是：天，不论几天，统一按一个分期（只生成一个分期）
		// 将 $this->zuqi 设置为 1，后续程序处理不变
		if( $this->zuqi_type == 1 ){
			//先按照天租期计算租金
			$this->zujin = $this->fenqi_amount = $this->zujin*$this->zuqi;
			$this->first_amount = $this->zujin+$this->yiwaixian;
			$this->fenqi_amount =round($this->amount/$this->zuqi,2);
			//然后将租期重置为1期【按天租赁：只在首月扣款】
			$this->zuqi = 1;
		}
		//0首付
		if($this->coupon_type == \zuji\coupon\CouponStatus::CouponTypeFirstMonthRentFree){
			$fenqi_price = ($this->all_amount-$this->yiwaixian)/$this->zuqi;
			$first = $fenqi_price-$this->discount_amount;
			$first = $first>0?$first:0;
			$first += $this->yiwaixian;
			$this->first_amount = $first;
			$this->fenqi_amount = $fenqi_price;
		}
		//固定金额
		elseif($this->coupon_type == \zuji\coupon\CouponStatus::CouponTypeFixed){
			$price = $this->all_amount-$this->yiwaixian-$this->discount_amount;
			$price = $price>0?$price:0;
			$this->fenqi_amount = $price/$this->zuqi;
			$first = $this->fenqi_amount+$this->yiwaixian;
			$this->first_amount =$first;
		}
		//递减优惠券
		elseif($this->coupon_type == \zuji\coupon\CouponStatus::CouponTypeDecline){
			$this->fenqi_amount = $this->zujin;
			$first = $this->fenqi_amount-$this->discount_amount;
			$this->first_amount =$first>=0?$first+$this->yiwaixian:$this->yiwaixian;
		}
		//不同支付方式呈现不同分期金额
		if($this->payment_type_id == Config::FlowerStagePay or $this->payment_type_id == Config::UnionPay){
			$this->fenqi_amount = $this->amount/$this->zuqi;
		}

		return array_merge($this->schema,[
			'instalment' => [
				'first_amount' => $this->first_amount,
				'fenqi_amount' => $this->fenqi_amount,
				'coupon_type' => $this->schema['coupon']['coupon_type']
			]
		]);
	}
	public function create():bool {

		if( !$this->flag ){
			return false;
		}
		$b = $this->componnet->create();
		if( !$b ){
			return false;
		}
		//支持分期支付方式
		$pay_type = [
				\zuji\Config::WithhodingPay
		];
		if(!in_array($this->payment_type_id,$pay_type)){
			return true;
		}
		if($this->coupon_type == \zuji\coupon\CouponStatus::CouponTypeDecline){
			return $this->diminishing_fenqi();
		}
		else{
			return $this->default_fenqi();
		}
	}
	//默认分期单生成
	function default_fenqi(){
		//获取订单id
		$order_id = $this->componnet->get_order_creater()->get_order_id();
		//重新初始化订单金额等相关数据
		$this->get_data_schema();
		// 租期数组
		$date  = get_terms($this->zuqi);
		// 默认分期
		for($i = 1; $i <= $this->zuqi; $i++){
			//代扣协议号
			$_data['agreement_no']  = $this->withholding_no;
			//订单ID
			$_data['order_id']        = $order_id;
			//还款日期(yyyymm)
			$_data['term']            = $date[$i];
			//第几期
			$_data['times']           = $i;
			if($i==1){
				//首期应付金额（分）
				$_data['amount']          = $this->first_amount;
				//优惠金额
				$_data['discount_amount'] = $this->zujin-($this->first_amount-$this->yiwaixian);
			}
			else{
				//其余应付金额（分）
				$_data['amount']          = $this->fenqi_amount;
				//优惠金额
				$_data['discount_amount'] = $this->zujin-$this->fenqi_amount;
			}

			$_data['unfreeze_status'] = 2;
			//支付状态 金额为0则为支付成功状态
			$_data['status']          = $_data['amount']>0?\zuji\payment\Instalment::UNPAID:\zuji\payment\Instalment::SUCCESS; //状态

			$ret = \hd_load::getInstance()->table('order2/order2_instalment')->create($_data);
			if(!$ret){
				$this->get_order_creater()->set_error('第'.$i.'条分期单创建失败,插入参数：'.json_encode($_data));
				return false;
			}
		}
		return true;
	}
	//递减式分期
	function diminishing_fenqi(){
		//获取订单id
		$order_id = $this->componnet->get_order_creater()->get_order_id();
		//重新初始化订单金额等相关数据
		$this->get_data_schema();
		// 租期数组
		$date  = get_terms($this->zuqi);
		//优惠金额
		$discount_amount = $this->discount_amount;
		// 默认分期
		for($i = 1; $i <= $this->zuqi; $i++){
			//代扣协议号
			$_data['agreement_no']  = $this->withholding_no;
			//订单ID
			$_data['order_id']        = $order_id;
			//还款日期(yyyymm)
			$_data['term']            = $date[$i];
			//第几期
			$_data['times']           = $i;

			if($discount_amount>$this->zujin){
				$discount_amount = $discount_amount-$this->zujin;
				$_data['amount'] = 0;
				$_data['discount_amount'] = $this->zujin;
			}
			else{
				$_data['discount_amount'] = $discount_amount;
				$_data['amount'] = $this->zujin - $discount_amount;
				$discount_amount = 0;
			}
			//首期应付金额（分）
			if($i==1){
				$_data['amount']  += $this->yiwaixian;
			}

			$_data['unfreeze_status'] = 2;
			//支付状态 金额为0则为支付成功状态
			$_data['status']          = $_data['amount']>0?\zuji\payment\Instalment::UNPAID:\zuji\payment\Instalment::SUCCESS; //状态

			$ret = \hd_load::getInstance()->table('order2/order2_instalment')->create($_data);
			if(!$ret){
				$this->get_order_creater()->set_error('第'.$i.'条分期单创建失败,插入参数：'.json_encode($_data));
				return false;
			}
		}
		return true;
	}
}
