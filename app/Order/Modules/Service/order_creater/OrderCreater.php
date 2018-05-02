<?php
namespace App\Order\Modules\Service\order_creater;

use App\Order\Models\Order;
use App\Order\Modules\Service\order_creater\CreditComponnet;
use App\Order\Modules\Service\order_creater\OrderCreaterComponnet;
use App\Order\Modules\Service\order_creater\UserComponnet;
use App\Order\Modules\Service\order_creater\SkuComponnet;
use App\Order\Modules\Service\order_creater\YidunComponnet;

/**
 * OrderCreater  订单创建器
 * 
 * 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderCreater implements OrderCreaterComponnet {
    /**
     * 订单编号
     * @var string
     */

    private $order_no = null;
    /**
     * 订单ID
     * @var int 
     */
    private $order_id = null;
	/**
	 * 错误提示
	 * @var string
	 */
    private $error = '';
	/**
	 * 错误码
	 * @var int
	 */
    private $errno = 0;
    /**
     * Sku组件
     * @var App\Order\Modules\Service\order_creater\SkuComponnet
     */
    private $sku_componnet = null;
    /**
     * 用户组件
     * @var App\Order\Modules\Service\order_creater\UserComponnet
     */
    private $user_componnet = null;

	
    
    /**
     * 构造器
     * @param OrderCreaterComponnet $componnet  订单创建器组件对象
     */
    public function __construct( $order_no=null ) {
		$this->order_no = $order_no;
    }
    /**
	 * 获取 订单编号
	 * @return string
	 */
    public function get_order_no(): string {
        return $this->order_no;
    }
	
	/**
	 * 获取订单ID
	 * @return int
	 */
    public function get_order_id(): int {
        return $this->order_id;
    }
	/**
	 * 
	 * 设置 User组件
	 * @param \oms\order_creater\UserComponnet $user_componnet
	 * @return \oms\OrderCreater
	 */
    public function set_user_componnet(UserComponnet $user_componnet){
        $this->user_componnet = $user_componnet;
        return $this;
    }
	/**
	 * 获取 User组件
	 * @return \oms\order_creater\UserComponnet
	 */
    public function get_user_componnet(){
        return $this->user_componnet;
    }

    /**
	 * 设置 Sku组件
	 * @param \oms\order_creater\SkuComponnet $sku_componnet
	 * @return \oms\OrderCreater
	 */
    public function set_sku_componnet(SkuComponnet $sku_componnet){
        $this->sku_componnet = $sku_componnet;
        return $this;
    }
    /**
     * 获取 Sku组件
     * @return \oms\order_creater\SkuComponnet
     */
    public function get_sku_componnet(): SkuComponnet{
        return $this->sku_componnet;
    }
    /**
	 * 获取 订单创建器
	 * @return \oms\OrderCreater
	 */
    public function get_order_creater(): OrderCreater {
        return $this;
    }

    /**
     * 设置 错误提示
     * @param string $error  错误提示信息
     * @return \oms\OrderCreater
     */
    public function set_error( string $error ): OrderCreater {
        $this->error = $error;
        return $this;
    }
	/**
	 * 获取 错误提示
	 * @return string
	 */
    public function get_error(): string{
        return $this->error;
    }
    
    /**
     * 设置 错误码
     * @param int $errno	错误码
     * @return \oms\OrderCreater
     */
    public function set_errno( $errno ): OrderCreater {
        $this->errno = $errno;
        return $this;
    }
	/**
	 * 获取 错误码
	 * @return int
	 */
    public function get_errno(): int{
        return $this->errno;
    }
	/**
     * 过滤
     * @return bool
     */
    public function filter():bool{
        $b = $this->user_componnet->filter();
        if( !$b ){
            return false;
        }
        $b = $this->sku_componnet->filter();
        if( !$b ){
            return false;
        }
        return true;
    }
	
	public function get_data_schema(): array{
		$user_schema = $this->user_componnet->get_data_schema();
		$sku_schema = $this->sku_componnet->get_data_schema();
		return array_merge(['order'=>[
			'order_no'=>$this->order_no
		]],$user_schema,$sku_schema);
	}

    /**
     * 创建订单
     * @return bool
     */
    public function create():bool{
        var_dump('创建订单...');
        // 执行 User组件
        $b = $this->user_componnet->create();
        if( !$b ){
            return false;
        }
        // 执行 Sku组件
        $b = $this->sku_componnet->create();
        if( !$b ){
            return false;
        }
        return true;
		// 创建订单
		$order_data = [
			'order_no' => $this->order_no,  // 编号
		];
		$order = new Order();
		$order->order_no =$this->order_no;
		$order->save();
		$this->order_id = $order->id;

		// 执行 User组件
        $b = $this->user_componnet->create();
        if( !$b ){
            return false;
        }
		// 执行 Sku组件
        $b = $this->sku_componnet->create();
        if( !$b ){
            return false;
        }
        return $b;
    }
    
}
