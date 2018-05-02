<?php
namespace oms\order_creater;

use oms\OrderCreater;
use zuji\coupon\Coupon;
/**
 * CouponComponnet 优惠券组件
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class CouponComponnet implements OrderCreaterComponnet {
    private $flag = true;
    private $componnet = null;
    private $coupon_no = '';
    private $coupon_name = '';  // 优惠券名称
    private $coupon_type = 0;   // 优惠券类型
    private $discount_amount = 0;      // 优惠金额
    private $user_id =0;
    private $coupon_id =0;

    public function __construct(OrderCreaterComponnet $componnet,$coupon_no) {
        $this->componnet = $componnet;
        $this->coupon_no = $coupon_no;
        // 用户ID
        $this->user_id = $this->get_order_creater()->get_user_componnet()->get_user_id();

    }
    
    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    
    public function filter(): bool {
        
        $filter_b =  $this->componnet->filter();
        //无优惠券
        if(!$this->coupon_no){
            return $this->flag && $filter_b;
        }
        // 根据优惠券编码，获取优惠券对象
        $validate_coupon = Coupon::validate_coupon(['coupon_no'=>$this->coupon_no,'user_id'=>$this->user_id]);

        if( !$validate_coupon ){
            $this->get_order_creater()->set_error('该优惠券不可用');
            return false;
        }

        $payment =$this->get_order_creater()->get_sku_componnet()->get_all_amount();
        $spu_id =$this->get_order_creater()->get_sku_componnet()->get_spu_id();
        $channel_id =$this->get_order_creater()->get_sku_componnet()->get_channel_id();
        $sku_id=$this->get_order_creater()->get_sku_componnet()->get_sku_id();
        $data =[
            'coupon_no'=>$this->coupon_no,
            'user_id'=>$this->user_id,
            'payment'=>$payment,
            'spu_id'=>$spu_id,
            'sku_id'=>$sku_id,
            'channel_id'=>$channel_id,
            'sku_id'=>$sku_id,
        ];

        $coupon = Coupon::get_coupon_row($data);

        // 判断优惠券有效性，然后获取优惠金额
        if($coupon['code']==0){
            $this->get_order_creater()->set_error($coupon['data']);
            return false;
        }
        $coupon['data'] = filter_array($coupon['data'], [
            'discount_amount' => 'required',
            'coupon_no' => 'required',
            'coupon_id' =>'required',
            'coupon_type' =>'required',
            'coupon_name' =>'required',
        ]);
        if(count($coupon['data'])!=5){
            $this->get_order_creater()->set_error("优惠券信息错误");
            return false;
        }
        $this->discount_amount = $coupon['data']['discount_amount'];
        $this->coupon_no = $coupon['data']['coupon_no'];
        $this->coupon_id = $coupon['data']['coupon_id'];
        $this->coupon_type = $coupon['data']['coupon_type'];
        $this->coupon_name = $coupon['data']['coupon_name'];
        // 订单ID
        $Creater = $this->get_order_creater();
        
        // sku
        $sku = $Creater->get_sku_componnet();
        
        // 优惠
        $sku->discount($this->discount_amount);
        
        return $this->flag && $filter_b;
    }
	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'coupon' => [
				'coupon_no' => $this->coupon_no,
				'coupon_name' => $this->coupon_name,
				'coupon_type' => $this->coupon_type,
				'discount_amount' => $this->discount_amount,
			]
		]);
	}
	
    public function create(): bool {
        if( !$this->flag ){
            return false;
        }
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        //无优惠券
        if(!$this->coupon_no){
            return true;
        }
        // 订单ID
        $Creater = $this->get_order_creater();
        $order_id = $Creater->get_order_id();
        
        $order2_coupon_table = \hd_load::getInstance()->table('order2/order2_coupon');

        $data =[
            'order_id'=>$order_id,
            'coupon_no'=>$this->coupon_no,
            'coupon_id'=>$this->coupon_id,
            'discount_amount'=>$this->discount_amount,
            'coupon_type'=>$this->coupon_type,
            'coupon_name'=>$this->coupon_name,
        ];
		$coupon_id = $order2_coupon_table->add($data);
        if(!$coupon_id){
            $this->get_order_creater()->set_error('保存订单优惠券信息失败');
            return false;
        }
        $b = Coupon::set_coupon_status($this->coupon_id);
        if(!$b){
            $this->get_order_creater()->set_error('更新优惠券状态失败');
            return false;
        }
        return true;
    }
}
