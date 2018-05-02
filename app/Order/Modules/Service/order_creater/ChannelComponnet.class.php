<?php
namespace App\Order\Modules\Service\order_creater;
use App\Order\Modules\Service\OrderCreater;
/**
 * ChannelComponnet 渠道主键
 * <p>注意：</p>
 * 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class ChannelComponnet implements \oms\order_creater\OrderCreaterComponnet {
    
    private $flag = true;
    
    private $componnet = null;
    
	/**
	 * app_id 主键
	 * @var int
	 */
	private $app_id = 0;
	/**
	 * app_id 名称
	 * @var string
	 */
	private $app_name = '';
	/**
	 * app_id 类型
	 * @var int
	 */
	private $app_type = 0;
	/**
	 * app_id 状态： 0：禁用；1：启用
	 * @var int
	 */
	private $app_status = 0;
	
	/**
	 * 渠道 主键
	 * @var int
	 */
	private $channel_id = 0;
	/**
	 * 渠道 名称
	 * @var string
	 */
	private $channel_name = '';
	/**
	 * 渠道商品是否独立
	 * @var string
	 */
	private $channel_alone_goods = '';
	/**
	 * 渠道 状态： 0：禁用；1：启用
	 * @var int
	 */
	private $channel_status = 0;
	
    
    public function __construct(OrderCreaterComponnet $componnet, $app_id) {
        $this->componnet = $componnet;
		
		$appid_service = \hd_load::getInstance()->service('channel/channel_appid');
		$info = $appid_service->get_info( $app_id, 'channel' );
		
		if( !$info || !$info['_channel'] ){
			var_dump( $app_id, $info );exit;
			throw new ComponnetException('[创建订单]获取渠道信用失败');
		}
		$this->app_id = intval($info['appid']['id']);
		$this->app_name = $info['appid']['name'];
		$this->app_type = intval($info['appid']['type']);
		$this->app_status = intval($info['appid']['status'])?1:0;
		$this->channel_id = intval($info['_channel']['id']);
		$this->channel_name = $info['_channel']['name'];
		$this->channel_alone_goods = intval($info['_channel']['alone_goods'])?1:0;
		$this->channel_status = intval($info['_channel']['status'])?1:0;
    }
	
    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    public function filter(): bool {
        $filter_b = $this->componnet->filter();
        //var_dump( '过滤 渠道...' );
		
        if( $this->app_status == 0 ){
            $this->get_order_creater()->set_error('appid已禁用');
            $this->flag = false;
        }
        if( $this->channel_status == 0 ){
            $this->get_order_creater()->set_error('渠道已禁用');
            $this->flag = false;
        }
        
		$_channel_id = $this->get_order_creater()->get_sku_componnet()->get_channel_id();
		
        if( $this->channel_alone_goods==1 ){
			if( $_channel_id != $this->channel_id ){
				$this->get_order_creater()->set_error('商品渠道错误');
				$this->flag = false;
			}
        }
		
		return $this->flag && $filter_b;
    }
    
	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'channel' => [
				'app_id' => $this->app_id,
				'app_name' => $this->app_name,
				'app_type' => $this->app_type,
				'app_status' => $this->app_status,
				'channel_id' => $this->channel_id,
				'channel_name' => $this->channel_name,
				'channel_status' => $this->channel_status,
				'channel_alone_goods' => $this->channel_alone_goods,
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
        //var_dump( '---------------------------保存渠道...' );
        //var_dump('AppID：'.$this->app_id);
        //var_dump('AppID名称：'.$this->app_name);
		
		// 订单ID
        $order_id = $this->componnet->get_order_creater()->get_order_id();
		$data = [
			'appid' => $this->app_id,
		];
		$order_table = \hd_load::getInstance()->table('order2/order2');
		$b = $order_table->where(['order_id'=>$order_id])->save($data);
		if( !$b ){
			$this->get_order_creater()->set_error('保存渠道信息失败');
			return false;
		}
        return true;
    }


}
