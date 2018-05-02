<?php
namespace oms\order_creater;

use \oms\OrderCreater;
use oms\state\State;
use zuji\Config;
use zuji\order\Order;

/**
 * CreditComponnet
 * <p>注意：</p>
 * <p>1)必须认证</p>
 * <p>2)信用分值最小600分</p>
 * <p>3)信用分值>=600分时，免押</p>
 * <p>4)必须人脸识别通过</p>
 * 
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class CreditComponnet implements \oms\order_creater\OrderCreaterComponnet {
    
    private $flag = true;
    
    private $componnet = null;
    
	private $user_id = 0;
	
    private $certified = 0;
    private $certified_platform = 0;    // 芝麻认证
    private $realname = '';    // 身份证姓名
    private $cert_no = '';      // 身份证号
    private $credit = 0;        // 信用分
    private $face = 0;			// 人脸识别
	
	/**
	 *身份认证是否一致【默认一致】；京东小白渠道需要通过该字段确认当前用户是否允许下单
	 * @var type 
	 */
	private $certified_flag = true;

	private $min_score = 0; //最小信用分数
	private $age = 0; //年龄

    
	private $risk = 0;			// 风控标识

	// 过期
	private $expired = true;
	private $has_order = 'N';
	private $has_service = 'N';

	/**
	 * 信用验证
	 * $credit Array
	 * [
	 * 	'min_score' => '最小信用分数',
	 *  'min_age' => '最小租机年龄'
	 *  'max_age' => '最大租机年龄'
	 * ]
	 * @param boolen $certified_flag 用户最新认证和用户表认证信息是否一致（true：一致；false：不一致）
	 */
    public function __construct(OrderCreaterComponnet $componnet,$certified_flag = true) {
		$this->certified_flag = !!$certified_flag;

        $this->componnet = $componnet;
		// 获取 用户ID
		$orderCreater = $this->get_order_creater();
		$userComponnet = $orderCreater->get_user_componnet();
		$this->user_id = $userComponnet->get_user_id();
		
		// 获取用户认证信息
		$member_table = \hd_load::getInstance()->table('member/member');
		$fields = ['certified','certified_platform','credit','face','risk','cert_no','realname','credit_time'];
		$info = $member_table->field($fields)->where(['id'=>$this->user_id])->find();

		if( $info ){
			$this->credit_time = intval( $info['credit_time'] );
			// 赋值
			$this->certified = $info['certified']?1:0;
			$this->certified_platform = intval($info['certified_platform']);
			$this->realname = $info['realname'];
			$this->cert_no = $info['cert_no'];
			$this->credit = intval($info['credit']);
			$this->face = $info['face']?1:0;

			$age =substr($this->cert_no,6,8);
			$now = date("Ymd");
			$this->age = intval(($now-$age)/10000);
			//throw new ComponnetException('[创建订单]获取用户信用失败');

			$this->risk = $info['risk']?1:0;
			
			// 是否过期
			if( (time()-$this->credit_time) <= 60*60 ){
				$this->expired = false;
			}
		}
    }


    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    public function filter(): bool {
        $filter_b = $this->componnet->filter();
        //var_dump( '过滤信用...' );
		
		
        if( $this->certified == 0 ){
            $this->get_order_creater()->set_error('账户尚未信用认证');
			$this->flag = false;
        }
		// 信用认证结果有效期
		if( $this->expired ){
            $this->get_order_creater()->set_error('信用认证过期');
			$this->flag = false;
		}
		//京东小白信用分过低禁止下单，认证信息不一致禁止下单（京东暂时无法支付押金2018-04-24；所有下单均为免押）
		if( $this->certified_platform==\zuji\certification\Certification::JdXiaoBai  &&  $this->credit<\zuji\Config::Jdxbxy_Score ) {
			$this->get_order_creater()->set_error('小白信用分过低');
			$this->flag = false;
		}
		//京东小白信用认证信息和用户表信息不一致禁止下单，认证信息不一致禁止下单（京东暂时无法支付押金2018-04-24；所有下单均为免押）
		if( $this->certified_platform==\zuji\certification\Certification::JdXiaoBai  &&  !$this->certified_flag ) {
			$this->get_order_creater()->set_error('当前用户已实名认证且和登录用户认证信息不一致');
			$this->flag = false;
		}
		// 一个账号只能有一个活跃订单或服务
		// 订单查询
		$order_table = \hd_load::getInstance()->table('order2/order2');
		// 用户ID查询订单
		$where = [
				'order_status'=> \zuji\order\OrderStatus::OrderCreated,
				'user_id'=>$this->user_id
		];
		$n = $order_table->get_count($where);
        if( $n > 0 ){
			$this->has_order = 'Y';
            $this->get_order_creater()->set_error('有未完成订单');
			$this->flag = false;
        }
		// 用户身份证查询订单
		$where = [
				'order_status'=> \zuji\order\OrderStatus::OrderCreated,
				'cert_no' => $this->cert_no
		];
		$n = $order_table->get_count($where);
        if( $n > 0 ){
			$this->has_order = 'Y';
            $this->get_order_creater()->set_error('有未完成订单');
			$this->flag = false;
        }
		
		// 服务查询
		$service_table = \hd_load::getInstance()->table('order2/order2_service');
		$table = $service_table->getTableName();
        $where = $table.'.`service_status`='.\zuji\order\ServiceStatus::ServiceOpen.' AND (O.`user_id`='. $this->user_id.' OR O.`cert_no`="'. $this->cert_no.'") AND `mianyajin`>0';
        $n =$service_table->join(config("DB_PREFIX").'order2 AS O ON '.$table.'.order_id=O.order_id')->where($where)->count($table.'.`service_id`');
        if( $n > 0 ){
			$this->has_service = 'Y';
            $this->get_order_creater()->set_error('有租用中订单');
			$this->flag = false;
        }
		
		// 信用分值>=600分时，免押
		/*if( $this->credit >= \zuji\Config::ZhiMa_Score ){
			$orderCreater = $this->get_order_creater();
			// sku 组件
			$skuComponnet = $orderCreater->get_sku_componnet();
			// 全部免押
			$skuComponnet->mianyajin();
			// 订单免押状态
			$this->get_order_creater()->set_mianya_status(1);
		}*/
		return $this->flag && $filter_b;
    }
    
	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'credit' => [
				// 已认证，通过人脸识别，通过风控，认证未过期
				'certified' => $this->certified&&!$this->expired,
				'certified_platform' => $this->certified_platform,
				'realname' => $this->realname,
				'cert_no' => $this->cert_no,
				'credit' => $this->credit,
				'age' =>$this->age,
				'face' => $this->face,
				'risk' => $this->risk,
				'credit_time' => $this->credit_time,
				'has_order' => $this->has_order,
				'has_service' => $this->has_service,
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
        //var_dump( '---------------------------保存信用...' );
        //var_dump('认证状态：'.$this->certified);
        //var_dump('认证平台：'.$this->certified_platform);
        //var_dump('真实姓名：'.$this->realname);
        //var_dump('身份证号：'.$this->cert_no);
        //var_dump('信用分值：'.$this->credit);
        //var_dump('人脸识别：'.$this->face);
		
		// 订单ID
        $order_id = $this->componnet->get_order_creater()->get_order_id();
		
		// 记录订单认证信息
		$data = [
			'certified' => $this->certified,
			'certified_platform' => $this->certified_platform,
			'realname' => $this->realname,
			'cert_no' => $this->cert_no,
			'credit' => $this->credit,
		];
		$order_table = \hd_load::getInstance()->table('order2/order2');
		$b = $order_table->where(['order_id'=>$order_id])->save($data);
		if( !$b ){
			$this->get_order_creater()->set_error('保存订单认证信息失败');
			return false;
		}
        return true;
    }


}
