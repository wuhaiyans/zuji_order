<?php
namespace App\Order\Modules\Service\order_creater;
use App\Order\Modules\Service\order_creater\OrderCreater;

class UserComponnet implements OrderCreaterComponnet {
    
	/**
	 * 组件
	 * @var \oms\order_creater\OrderCreaterComponnet
	 */
    private $componnet = null;
	
	private $flag = true;
	
	/**
	 * 用户ID
	 * @var int 
	 */
    private $user_id = 0;
	
	/**
	 * 用户名
	 * @var string 
	 */
    private $mobile = '';

	/**
	 * 代扣协议号
	 * @var string
	 */
	private $withholding_no = '';
	/**
	 * 账户是否锁定
	 * @var int 
	 */
    private $islock = 0;
	/**
	 * 账户是否 允许下单
	 * @var int 
	 */
    private $block = 0;
    
    public function __construct(OrderCreaterComponnet $componnet, int $user_id) {
//        $this->componnet = $componnet;
//		$member_table = \hd_load::getInstance()->table('member/member');
//		$info = $member_table->field('id,username,withholding_no,islock,block')->where(['id'=>$user_id])->find();
//		if( !$info ){
//			throw new ComponnetException('下单获取用户失败');
//		}
//		$this->user_id = intval($info['id']);
//		$this->mobile = $info['username'];
//		$this->withholding_no = $info['withholding_no'];
//		$this->islock = intval($info['islock'])?1:0;
//		$this->block = intval($info['block'])?1:0;
    }
    
	/**
	 * 获取 用户ID
	 * @return int
	 */
	public function get_user_id(){
		return $this->user_id;
	}
	
	
    
    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    public function filter():bool {
        //var_dump( '过滤用户...' );
		if( $this->islock ){
			$this->get_order_creater()->set_error('账号锁定');
			$this->flag = false;
		}
		if( $this->block ){
			$this->get_order_creater()->set_error('由于您的退款次数过多，账户暂时无法下单，请联系客服人员！');
			$this->flag = false;
		}
		
        return $this->flag;
    }
    
	public function get_data_schema(): array{
		return [
			'user' => [
				'user_id' => $this->user_id,
				'mobile' => $this->mobile,
				'withholding_no'=> $this->withholding_no,
			]
		];
	}
	
    public function create():bool {
        if( !$this->flag ){
            return false;
        }
		
        var_dump( '---------------------------保存用户信息...' );
        var_dump('用户ID：'.$this->user_id);
        var_dump('用户名：'.$this->mobile);return true;
        
		// 订单ID
		$order_id = $this->componnet->get_order_creater()->get_order_id();
        
		// 写入用户信息
		$data = [
			'user_id' => $this->user_id,
			'mobile' => $this->mobile,
		];
		$order_table = \hd_load::getInstance()->table('order2/order2');
		$b = $order_table->where(['order_id'=>$order_id])->save($data);
		if( !$b ){
			$this->get_order_creater()->set_error('保存订单用户信息失败');
			return false;
		}
        return true;
    }

}
