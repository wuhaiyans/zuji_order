<?php
namespace oms\order_creater;

use oms\OrderCreater;
use zuji\Config;

/**
 * 
 *
 * @author limin <limin@huishoubao.com.cn>
 */
class DepositComponnet implements OrderCreaterComponnet {
    
    private $flag = true;
    
    /**
     *
     * @var OrderCreaterComponnet 
     */
    private $componnet = null;
	private $schema = null;
	//支付类型id
	private $payment_type_id = 0;
	//年龄
	private $age = 0;
	//信用分
	private $credit = 0;
	//spu商品id
    private $spu_id = 0;
	//商品押金
    private $yajin = 0;
	//押金减免金额
	private $jianmian = 0;

	//是否满足押金减免条件
	private $deposit = true;


    public function __construct(OrderCreaterComponnet $componnet,$payment_type_id,$certified_flag=true) {
        $this->componnet = $componnet;

		$this->payment_type_id = $payment_type_id;

		$this->filter();
		$this->schema = $this->get_data_schema();

		$this->spu_id = $this->schema['sku']['spu_id'];
		$this->yajin = $this->schema['sku']['yajin'];
		$this->credit = $this->schema['credit']['credit']?$this->schema['credit']['credit']:0;
		$this->age = $this->schema['credit']['age']?$this->schema['credit']['age']:0;

		/* 
		 * 2018-02-22 liuhongxing 暂时去掉 人脸识别 限制条件（为 芝麻活动 提高订单量）
		 * 2018-03-05 liuhongxing 恢复人脸识别 限制条件
		*/
		//根据用户实名认证信息是否一致初始化订单是否满足押金键名条件
		$this->deposit = !!$certified_flag;
		//未通过认证人脸识别
		if($this->schema['credit']['face']==0){
			$this->deposit = false;
		}
		//未通过风控验证
		if($this->schema['credit']['risk']==0){
			$this->deposit = false;
		}
		
		//未通过蚁盾验证
		if($this->schema['yidun']['decision']== \oms\order_creater\YidunComponnet::RISK_REJECT){
			$this->deposit = false;
		}
		//京东小白信用押金全免设置（配合银联支付）
		if( $this->schema['credit']['certified_platform']== \zuji\certification\Certification::JdXiaoBai ) {
			$this->deposit = true;
		}
    }

    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    public function filter(): bool{
		$filter_b =  $this->componnet->filter();
		if($this->deposit && $this->payment_type_id>0){
			//支付押金规则
			$rule = \hd_load::getInstance()->service("payment/payment_rule");
			$rule = $rule->get_rule_info($this->spu_id,$this->payment_type_id,$this->credit,$this->age,$this->yajin);
			$this->jianmian = $rule['jianmian'];
			$this->get_order_creater()->get_sku_componnet()->discrease_yajin($this->jianmian);
		}
		return $this->flag && $filter_b;
    }
    
	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'deposit' => [
				'jianmian' => $this->jianmian,
				'yajin' => $this->yajin,
				'payment_type_id'=>$this->payment_type_id,
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
		return true;
        
    }

}
