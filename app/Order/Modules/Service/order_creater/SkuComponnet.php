<?php
namespace App\Order\Modules\Service\order_creater;
use App\Order\Modules\Service\order_creater\OrderCreater;


class SkuComponnet implements OrderCreaterComponnet {
    
    private $flag = true;
    
    /**
     *
     * @var OrderCreaterComponnet 
     */
    private $componnet = null;
    
	// sku
    private $sku_name = '';
    private $sku_id = 0;
    private $spu_id = 0;
    private $zujin = 0;
    private $yajin = 0;
    private $zuqi = 0;
	private $zuqi_type = 0;
    private $chengse = 0;
    private $yiwaixian = 0;
    private $yiwaixian_cost = 0;
    private $stock = 0;// 库存量
    private $specs = '';	// 规格
	private $status = 0;
	private $thumb = '';
    
	// spu
    private $spu_name = '';
    private $brand_id = 0;
    private $model_id = 0;
    private $category_id = 0;
    private $channel_id = 0;
	//合同模板id
	private $contract_id = 0;
	//
    private $mianyajin = 0;
    private $all_amount = 0;// 商品总金额
    private $amount = 0;// 订单金额
    private $discount_amount = 0; //优惠金额
	private $payment_type_id = 0; //支付方式
    private $market_price=0; // 商品市场价
	private $buyout_price = 0;// 买断价格
    //订单商品ID
    private $goods_id = 0;

    //商品押金规则ID
    private $deposit_id = 0;

    public function __construct(OrderCreaterComponnet $componnet,$sku_id,int $payment_type_id) {
//        $this->componnet = $componnet;
//        $this->sku_id = $sku_id;
//		$this->payment_type_id = $payment_type_id; // 支付方式
//
//		// sku
//		$sku_table = \hd_load::getInstance()->table('goods/goods_sku');
//		$fields = ['sku_id','spu_id','sku_name','status','number','shop_price','yajin','zuqi','zuqi_type','chengse','spec','market_price'];
//		$sku_info = $sku_table->field($fields)->where(['sku_id'=>$sku_id])->find(['lock'=>true]);
//		$this->spu_id = $sku_info['spu_id'];
//
//		if(!$sku_info){
//			throw new ComponnetException('[创建订单]获取sku失败');
//		}
//		if($sku_info['spu_id']<1){
//			throw new ComponnetException('[创建订单]spu_id错误');
//		}
//
//		// spu
//		$spu_table = \hd_load::getInstance()->table('goods/goods_spu');
//		$fields = ['id','name','catid','brand_id','yiwaixian','yiwaixian_cost','channel_id','payment_rule_id','thumb','contract_id'];
//		$spu_info = $spu_table->field($fields)->where(['id'=>$sku_info['spu_id']])->find();
//		if(!$spu_info){
//			throw new ComponnetException('[创建订单]spu信息获取失败');
//		}
//
//		$this->sku_id = intval($sku_info['sku_id']);
//		$this->spu_id = intval($sku_info['spu_id']);
//		$this->zujin = $sku_info['shop_price']*100;
//		$this->yajin = $sku_info['yajin']*100;
//		$this->mianyajin = $sku_info['mianyajin']*100;
//		$this->zuqi = intval($sku_info['zuqi']);
//		$this->zuqi_type = intval($sku_info['zuqi_type']);
//		$this->chengse = intval($sku_info['chengse']);
//		$this->stock = intval($sku_info['number']);
//
//        $this->market_price = $sku_info['market_price']*100;
//		$this->buyout_price = $this->market_price*1.2-$this->zujin*$this->zuqi;
//
//		// 格式化 规格
//		$_specs = [];
//        foreach($sku_info['spec'] as $it){
//            $_specs[] = filter_array($it, [
//				'id' => 'required',
//				'name' => 'required',
//				'value' => 'required',
//            ]);
//        }
//		$this->specs = $_specs;
//		$this->thumb = $spu_info['thumb'];
//		$this->status = intval($sku_info['status'])?1:0;
//
//		$this->sku_name = $spu_info['name'];// sku_name 使用 spu 的 name 值
//		$this->spu_name = $spu_info['name'];
//		$this->brand_id = intval($spu_info['brand_id']);
//		$this->model_id = intval($spu_info['model_id']);
//		$this->category_id = intval($spu_info['catid']);
//		$this->channel_id = intval($spu_info['channel_id']);
//		$this->yiwaixian = $spu_info['yiwaixian']*100;
//		$this->yiwaixian_cost = $spu_info['yiwaixian_cost']*100;
//		$this->contract_id =$spu_info['contract_id'];
//        // 计算金额
//        $this->amount = $this->all_amount = (($this->zujin * $this->zuqi) + $this->yiwaixian );
//
//        if( $this->amount<0 ){
//			throw new ComponnetException('[创建订单]商品价格错误');
//        }
//        
    }
    
	/**
	 * 获取商品所在的 渠道
	 * @return int
	 */
	public function get_channel_id(){
		return $this->channel_id;
	}
	
	// 订单原始总金额
    public function get_all_amount() {
        return $this->all_amount;
    }
	// 当前订单金额
    public function get_amount() {
        return $this->amount;
    }
	// 当前优惠金额
    public function get_discount_amount() {
        return $this->discount_amount;
    }
    //当前sku_id
    public function get_sku_id(){
	    return $this->sku_id;
    }

    //当前spu_id
	public function get_spu_id(){
		return $this->spu_id;
	}

	//当前payment_type
	public function get_payment_type_id(){
		return $this->payment_type_id;
	}

	//当前goods_id
    public function get_goods_id(){
	    return $this->goods_id;
    }

    //获取当前碎屏险
    public function get_yiwaixian(){
        return $this->yiwaixian;
    }
	
    //获取当前碎屏险成本价
    public function get_yiwaixian_cost(){
        return $this->yiwaixian_cost;
    }

    //获取sku 市场价
    public function get_market_price(){
        return $this->market_price;
    }

	//获取商品押金
	public function get_yajin(){
		return $this->yajin;
	}

    /**
     * 
     * 增加订单金额
     * @param int $amount
     * @return \oms\order_creater\SkuComponnet
     */
    public function increase_amount(int $amount): SkuComponnet{
        if( $amount<0 ){
            return $this;
        }
        
        $this->amount += $amount;
        // 最终金额>=0
        if( $this->amount<0 ){
            $this->amount = 0;
        }
        return $this;
    }
    
    /**
     * 优惠金额
     * <p>如果优惠金额 大于 订单金额时，优惠金额值取总订单额进行优惠</p>
     * @param int $amount  金额值，单位：分；必须>=0
     * @return \oms\order_creater\SkuComponnet
     */
    public function discount(int $amount): SkuComponnet{
        if( $amount<0 ){
            return $this;
        }
		$price = $this->amount-$this->yiwaixian;
        // 优惠金额最多等于总金额
        if( $amount >= $price ){
            $amount = $price;
        }
        $this->amount -= $amount;// 更新总金额
        $this->discount_amount += $amount;// 更新优惠金额
        return $this;
    }
    
    /**
     * 免押
     * @param int $amount
     * @return \oms\order_creater\SkuComponnet
     */
    public function discrease_yajin(int $amount): SkuComponnet{
        if( $amount<0 ){
            return $this;
        }
        // 优惠金额 大于 总金额 时，总金额设置为0.01
		if( $amount >= $this->yajin ){
            $amount = $this->yajin;
        }
        $this->yajin -= $amount;// 更新押金
        $this->mianyajin += $amount;// 更新免押金额
        return $this;
    }
    /**
     * 全部免押
     * @return \oms\order_creater\SkuComponnet
     */
    public function mianyajin(): SkuComponnet{
        $this->mianyajin += $this->yajin;// 更新免押金额(一定要兼顾已经免押的金额，所以是 +=)
        $this->yajin = 0;// 更新押金
        return $this;
    }
    
    
    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    public function filter(): bool{
        //var_dump( '过滤sku...' );
		
		// 库存量
		if( $this->stock<1 ){
			$this->get_order_creater()->set_error('[创建订单]商品库存不足');
			$this->flag = false;
		}
		// 商品上下架状态
		if( $this->status!=1 ){
			$this->get_order_creater()->set_error('[创建订单]商品已下架');
			$this->flag = false;
		}
		// 成色 100,99,90,80,70,60
		if( $this->chengse<1 || $this->chengse>100 ){
			$this->get_order_creater()->set_error('[创建订单]商品成色错误');
			$this->flag = false;
		}
		if( $this->zuqi_type == 1 ){ // 天
			// 租期[1,12]之间的正整数
			if( $this->zuqi<1 || $this->zuqi>31 ){
				$this->get_order_creater()->set_error('[创建订单]商品租期错误');
				$this->flag = false;
			}
		}else{
			// 租期[1,12]之间的正整数
			if( $this->zuqi<1 || $this->zuqi>12 ){
				$this->get_order_creater()->set_error('[创建订单]商品租期错误');
				$this->flag = false;
			}
		}
		// sku 必须有 月租金, 且不可低于系统设置的最低月租金
		$zujin_min_price = config('ZUJIN_MIN_PRICE');// 最低月租金
		if( $this->zujin < ($zujin_min_price*100) ){
			$this->get_order_creater()->set_error('[创建订单]商品租金错误');
			$this->flag = false;
		}
		// 押金必须
		if( $this->yajin < 1 && $this->payment_type_id != Config::MiniAlipay){
			$this->get_order_creater()->set_error('[创建订单]商品押金错误');
			$this->flag = false;
		}
		// 规格
		$must_spec_id_list = \zuji\Goods::getMustSpecIdList();
		$spec_ids = array_column($this->specs, 'id');
		$spec_id_diff = array_diff($must_spec_id_list, $spec_ids);
		if( count($spec_id_diff)>0 ){
			$this->get_order_creater()->set_error('[创建订单]商品缺少必要规格');
			$this->flag = false;
		}
		
        return $this->flag;
    }
    
	public function get_data_schema(): array{
		return [
			'sku' => [
				'sku_id' => $this->sku_id,
				'spu_id' => $this->spu_id,
				'sku_name' => $this->sku_name,
				'spu_name' => $this->spu_name,
				'brand_id' => $this->brand_id,
				'category_id' => $this->category_id,
				'specs' => $this->specs,
				'thumb' => $this->thumb,
				'yiwaixian' => $this->yiwaixian,
				'yiwaixian_cost' => $this->yiwaixian_cost,
				'zujin' => $this->zujin,
				'yajin' => $this->yajin,
				'mianyajin' => $this->mianyajin,
				'zuqi' => $this->zuqi,
				'zuqi_type' => $this->zuqi_type,
				'buyout_price' => $this->buyout_price,
				'market_price' => $this->market_price,
				'chengse' => $this->chengse,
				'amount' => $this->amount,
				'discount_amount' => $this->discount_amount,
				'all_amount' => $this->all_amount,
				'payment_type_id'=>$this->payment_type_id,
				'contract_id'=>$this->contract_id,
				'stock' => $this->stock,
			]
		];
	}
	
    public function create(): bool {
        if( !$this->flag ){
            return false;
        }
        var_dump("创建SKU");
        return true;
                
		// 订单ID
        $order_id = $this->componnet->get_order_creater()->get_order_id();
        //业务类型
        $business_key = $this->componnet->get_order_creater()->get_business_key();
        
		// 保存 商品信息
		$goods_data = [
			'order_id' => $order_id,
			'sku_id' => $this->sku_id,
			'spu_id' => $this->spu_id,
			'sku_name' => $this->sku_name,
			'brand_id' => $this->brand_id,
			'category_id' => $this->category_id,
			'specs' => \zuji\order\goods\Specifications::input_format($this->specs),
			'zujin' => $this->zujin,
			'yajin' => $this->yajin,
			'mianyajin' => $this->mianyajin,
			'yiwaixian' => $this->yiwaixian,
			'yiwaixian_cost' => $this->yiwaixian_cost,
			'zuqi' => $this->zuqi,
			'zuqi_type' => $this->zuqi_type,
			'chengse' => $this->chengse,
			'create_time' => time(),
		];
		$order2_goods = \hd_load::getInstance()->table('order2/order2_goods');
		$goods_id = $order2_goods->add($goods_data);
		if( !$goods_id ){
			$this->get_order_creater()->set_error('[创建订单]商品保存失败');
			return false;
		}
        $this->goods_id =$goods_id;
        // 租机业务下单减少库存
        if($business_key == Business::BUSINESS_ZUJI){
            //sku库存 -1
            $sku_table =\hd_load::getInstance()->table('goods2/goods_sku');
            $spu_table=\hd_load::getInstance()->table('goods2/goods_spu');

            $sku_data['sku_id'] =$this->sku_id;
            $sku_data['number'] = ['exp','number-1'];
            $add_sku =$sku_table->save($sku_data);
            if(!$add_sku){
                $this->get_order_creater()->set_error('[创建订单]商品库存减少失败');
                return false;
            }
            $spu_data['id'] =$this->spu_id;
            $spu_data['sku_total'] = ['exp','sku_total-1'];
            $add_spu =$spu_table->save($spu_data);
            if(!$add_spu){
                $this->get_order_creater()->set_error('[创建订单]商品库存减少失败');
                return false;
            }
        }

//		// 测试环境;
//		if( $_SERVER['ENVIRONMENT'] == 'test' ){
//			// 测试优惠价格 = 租期数
//			if( $this->discount_amount>0 ){
//				$this->discount_amount = $this->zuqi;
//			}else{
//				$this->discount_amount = 0;
//			}
//			
//			// 测试意外险 1
//			$this->yiwaixian = 1;
//			
//			// 测试 月租金 2分（支持每月优惠1分）
//			$this->zujin = 2;
//			
//			// 测试 总金额
//			$this->all_amount = $this->zuqi*$this->zujin + $this->yiwaixian;
//			
//			// 测试 待支付金额
//			$this->amount = $this->all_amount - $this->discount_amount;
//		}

		// 保存订单商品信息
		$data = [
			'goods_id' => $goods_id,
			'goods_name' => $this->spu_name,
			'chengse' => $this->chengse,
			'zuqi' => $this->zuqi,
			'zuqi_type' => $this->zuqi_type,
			'zujin' => $this->zujin,
			'yajin' => $this->yajin,
			'mianyajin' => $this->mianyajin,
			'yiwaixian' => $this->yiwaixian,
			'amount' => $this->amount,
			'buyout_price' => $this->buyout_price,
			'discount_amount' => $this->discount_amount,
			'all_amount' => $this->all_amount,
			'payment_type_id'=>$this->payment_type_id
		];
		$order_table = \hd_load::getInstance()->table('order2/order2');
		$b = $order_table->where(['order_id'=>$order_id])->save($data);
		if( !$b ){
			$this->get_order_creater()->set_error('[创建订单]更新订单商品信息失败');
			return false;
		}
        return true;
        
    }

}
