<?php
namespace oms\order_creater;
use \oms\OrderCreater;
/**
 * 
 *
 * @author limin <limin@huishoubao.com.cn>
 */
class UserWithholding implements OrderCreaterComponnet {
    
	/**
	 * 组件
	 * @var \oms\order_creater\OrderCreaterComponnet
	 */
    private $componnet = null;
	
	private $flag = true;

	/**
	 * 代扣签约号
	 * @var int 
	 */
    private $withholding_no= '';

    
    public function __construct(OrderCreaterComponnet $componnet) {
        $this->componnet = $componnet;
		
		// 支付方式
		$sku_componnet = $this->get_order_creater()->get_sku_componnet();
		$this->payment_type_id = $sku_componnet->get_payment_type_id();

		if( $this->payment_type_id == \zuji\Config::WithhodingPay ){
			// 用户ID
			$user_componnet = $this->get_order_creater()->get_user_componnet();
			$this->user_id = $user_componnet->get_user_id();

			// 用户代扣协议号
			$member_table = \hd_load::getInstance()->table('member/member');
			$info = $member_table->field('withholding_no')->where(['id'=>$this->user_id])->find();
			//\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[创建订单]查询用户代扣协议', $info);
			if( !$info ){
				throw new ComponnetException('下单查询用户代扣协议编号失败');
			}
			if( $info['withholding_no'] ){
				$this->withholding_no = $info['withholding_no'];
			}
			// 代扣时进行初始化组件
			
			if( $this->withholding_no ){

				// 更新用户签约协议状态
				$withholding_table = \hd_load::getInstance()->table('payment/withholding_alipay');

				// 一个合作者ID下同一个支付宝用户只允许签约一次
				$where = [
					'user_id' => $this->user_id,
					'agreement_no' => $this->withholding_no,
				];
				$withholding_info = $withholding_table->field(['id','user_id','partner_id','alipay_user_id','agreement_no','status'])->where( $where )->limit(1)->find();
				if( !$withholding_info ){// 查询失败
					\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[创建订单]查询用户代扣协议失败', $where);
					throw new ComponnetException('下单查询用户代扣协议信息失败');
				}
				// 支付宝用户号
				$this->alipay_user_id = $withholding_info['alipay_user_id'];
			}

		}
    }

    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    public function filter():bool {
		$filter_b =  $this->componnet->filter();

		// 代扣支付方式时，进行判断
	    if( $this->payment_type_id == \zuji\Config::WithhodingPay ){
			if( !$this->withholding_no ){
				$this->get_order_creater()->set_error('[下单][代扣组件]未签约代扣协议');
				return false;
			}
			//--网络查询支付宝接口，获取代扣协议状态----------------------------------
			try {
				$withholding = new \alipay\Withholding();
				$status = $withholding->query( $this->alipay_user_id );
				if( $status=='Y' ){
					$this->flag = true;
				}else{
					$this->get_order_creater()->set_error('[下单][代扣组件]用户已经解约代扣协议');
					$this->flag = false;
					$this->withholding_no = '';// 用户已解约，清空代扣协议号
				}
			} catch (\Exception $exc) {
				\zuji\debug\Debug::error(\zuji\debug\Location::L_Withholding, '[下单][代扣组件]支付宝接口查询用户代扣协议出现异常', $exc->getMessage());
				$this->get_order_creater()->set_error('[下单][代扣组件]支付宝接口查询用户代扣协议出现异常');
				$this->flag = false;
			}
        }
		return $this->flag && $filter_b;
    }
    
	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'withholding' => [
				'withholding_no' => strlen($this->withholding_no)?true:false,
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
		return true;
    }

}
