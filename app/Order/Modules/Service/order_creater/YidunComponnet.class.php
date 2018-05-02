<?php
namespace oms\order_creater;

use \oms\OrderCreater;
use zuji\debug\Debug;
use zuji\debug\Location;
/**
 * 蚁盾接口调取类
 * Created by IDE.
 * @author: <yaodongxu@huishoubao.com.cn>
 * Date: 2018/2/11 下午 16:04
 * @copyright (c) 2018, Huishoubao
 */
class YidunComponnet implements \oms\order_creater\OrderCreaterComponnet {
	/**
	 * 风险类型：可接受的风险，无风险
	 */
	const RISK_ACCEPT = 'accept';
	/**
	 * 风险类型：不可接受的风险，高风险
	 */
	const RISK_REJECT = 'reject';
	/**
	 * 风险类型：用户根据自己业务模型进行验证，中风险
	 */
	const RISK_VALIDATE = 'validate';
	/**
	 *接口调取地址
	 * @var string
	 */

	/**
	 *接口返回结果【默认有风险】
	 * @var boolen true无风险；false有风险
	 */
	protected $flag = true;
	/**
	 * 风险验证
	 */
    public function __construct(OrderCreaterComponnet $componnet, $address_id='') {

        $this->componnet = $componnet;
		//蚁盾请求url
		$this->url = isset($_SERVER['yidunUrl']) ? $_SERVER['yidunUrl'] : $this->url;
		// 获取 用户ID
		$orderCreater = $this->get_order_creater();
		$userComponnet = $orderCreater->get_user_componnet();
		$this->user_id = $userComponnet->get_user_id();

		$this->address_id   = $address_id;
		// 获取用户认证信息
		$member_table = \hd_load::getInstance()->table('member/member');
		$fields = ['id as user_id','realname as user_name','mobile','email','login_ip as ip','cert_no','register_time as user_reg_time'];
		$info = $member_table->field($fields)->where(['id'=>$this->user_id])->find();
		//拼接用户的其他信息
		$info['user_agent'] = $_SERVER['HTTP_USER_AGENT'];


		$address_table = \hd_load::getInstance()->table('member2/member_address');
		$address_info = $address_table->get_address_info($this->user_id);
		
		if($address_info){
			$address_info = $address_info[0];
			$this->address_id = $address_info['id'];
			$this->district_id = $address_info['district_id'];
			// 查询 省市区ID和名称
			$district_service = \hd_load::getInstance()->service('admin/district');
			$district_info = $district_service->get_info($this->district_id);
			$city_info = $district_service->get_info($district_info['parent_id']);
			$province_info = $district_service->get_info($city_info['parent_id']);
			
			$info['receive_name'] 		= $address_info['name'];
			$info['receive_mobile'] 	= $address_info['mobile'];
			$info['receive_address'] 	= $address_info['address'];
			$info['receive_county'] 	= $district_info['name'];
			$info['receive_city'] 		= $city_info['name'];
			$info['receive_province'] 	= $province_info['name'];
		}

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
			$info['platform'] = 'ios';
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
			$info['platform'] = 'android';
		}elseif( PHP_OS=='WIN' ){
			$info['platform'] = 'web';
		}else{
			$info['platform'] = '';
		}

		//调取蚁盾风控验证接口
		$this->__check_risk($info);
    }
    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    public function filter(): bool{
		return $this->flag && $this->componnet->filter();
    }
    public function create(): bool {
        if( !$this->flag ){
            return false;
        }
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }

        $yidun_table = \hd_load::getInstance()->table('order2/order2_yidun');
        $yidun_schema =$this->get_data_schema();
        $yidun_data =[
            'order_id'=>$this->get_order_creater()->get_order_id(),
            'verify_id' => $yidun_schema['yidun']['verify_id'],
            'verify_uri' => $yidun_schema['yidun']['verify_uri'],
            'decision' => $yidun_schema['yidun']['decision'],
            'score' => $yidun_schema['yidun']['score'],
			'strategies' =>$yidun_schema['yidun']['strategies'],
            'level' => $yidun_schema['yidun']['level'],
            'yidun_id' => $yidun_schema['yidun']['yidun_id'],
        ];
        $b =$yidun_table->add($yidun_data);
        if(!$b){
            $this->get_order_creater()->set_error('蚁盾数据存储失败');
            return false;
        }

		return true;
    }
	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'yidun' => [
				'verify_id' => $this->verify_id,
				'verify_uri' => $this->verify_uri,
				'decision' => $this->decision,
				'score' => $this->score,
				'level' => $this->level,
				'strategies' => $this->strategies,
				'yidun_id' => $this->yidun_id,
			]
		]);
	}
	/**
	 * 蚁盾验证用户风险接口
	 * @param array $data
	 * $data = [
	 *		"userName" => '',        //string,用户姓名
	 *		"userId" => '',			 //string,用户ID
	 *		"mobile" => '',			 //string,用户手机号
	 *		"email" => '',			 //string,用户邮箱
	 *		"apdIdToken" => '',		 //string,
	 *		"ip" => '',				 //string,用户ip
	 *		"wifiMac" => '',		 //string,mac地址
	 *		"imei" => '',			 //string,用户imei
	 *		"imsi" => '',			 //string,用户imsi
	 *		"latitude" => '',		 //string,纬度
	 *		"longitude" => '',		 //string,经度
	 *		"platform" => '',		 //string,平台，ios,android,windows
	 *		"userAgent" => '',		 //string,userAgent
	 *		"certNo" => '',			 //string,身份证号
	 * ]
	 * @return boolen true接口验证；false接口验证有误
	 */
	private function __check_risk($data) {
		//过滤参数
		if( !$this->__parse_data($data)){
			$this->get_order_creater()->set_error(get_error());
			$this->flag = false;
			return false;
		}
		//调用curl进行post请求
		$headers = array(
			"Content-type: application/json;charset='utf-8'", 
			"Accept: application/json", 
			"Cache-Control: no-cache", 
			"Pragma: no-cache", 
		);
		//-+--------------------------------------------------------------------
		// | 获取redis缓存数据
		//-+--------------------------------------------------------------------
		$redis_key = 'zuji_user_yidun_result_'.$data['key_value_map']['user_id'];
		$redis_hash_data = $data;
		unset($redis_hash_data['key_value_map']['gmt_occur']);
		unset($redis_hash_data['key_value_map']['user_agent']);
		$redis_hash = md5(json_encode($redis_hash_data));
		//获取redis实例
        $redis = \zuji\cache\Redis::getInstans();
        $redis_result = $redis->get($redis_key);
		//判断缓存获取结果并判断
        if( $redis_result ){
			$redis_data = json_decode($redis_result,true);
			//缓存存在并且不为空，返回数据
			if( $redis_data && $redis_data['hash'] == $redis_hash ) {
				$this->verify_id = $redis_data['verify_id'];
				$this->verify_uri = $redis_data['verify_uri'];
				$this->decision = $redis_data['decision'];
				$this->score = $redis_data['score'];
				$this->level = $redis_data['level'];
				$this->strategies = $redis_data['strategies'];
				$this->yidun_id = $redis_data['yidun_id'];
				//蚁盾接口返回值有无问题，目前按照所有都是无风险处理
				$this->flag = true;
				return true;
			}
        }
		
		
		//-+--------------------------------------------------------------------
		// | 网络调取接口请求数据
		//-+--------------------------------------------------------------------
		$result = \zuji\Curl::post(config('YIDUN_REQUEST_URL'), json_encode($data), $headers);
		//蚁盾结果json串转数组
		$result = json_decode($result,true);
		//蚁盾返回值错误时，录入debug
		if( !$result || !isset($result['code']) || $result['code'] != 'OK' ) {
			Debug::error(Location::L_Order, '蚁盾数据获取失败', ['data'=> get_error(),'params'=>$data,'result'=>$result]);
		}
		//正常的$result的参数信息
		/*
		 $result = {
			"id":"|738ad825-e560-4be9-a52c-8a7b5203ba8e",
			"code":"OK",
			"message":"业务处理成功！",
			"verifyId":null,
			"verifyUri":null,
			"decision":"accept",
			"knowledge":{
				"traceId":"bda8b3da-f0d9-464f-9d5b-64f466fc59af",
				"event":{
					"content":null,
					"userName":null,
					"amt":null,
					"orderId":null,
					"mac":null,
					"cardNo":null,
					"cookieId":null,
					"pageName":null,
					"titile":null,
					"rdsContent":null,
					"rdsSource":null,
					"platform":"linux",
					"certNo":"123456",
					"userAgent":"linux",
					"code":"EC_LOGIN",
					"mobile":"13555554444",
					"imsi":"467894",
					"latitude":"33.525",
					"email":"sdfdf@hotmail.com",
					"userId":"123456",
					"wifiMac":"12:22",
					"longitude":"36.222",
					"apdIdToken":"1231",
					"ip":"0.0.0.0",
					"imei":"1234567941"
				},
				"identification":{
					"certNo":null,
					"mobile":null
				},
				"code":"EC_LOGIN"
			},
			"models":[
				{
					"code":"",
					"score":"50.02"
				}
			],
			"strategies":[
				{
					"id":"",
					"name":"",
					"level":"",
					"decision":""
				}
			]
		}
		*/

		$models 	= $this->__parse_is_set('models', $result, false);
		$strategies = $this->__parse_is_set('strategies', $result, false);
		
		
		$this->verify_id = $this->__parse_is_set('verifyId', $result);
		$this->verify_uri = $this->__parse_is_set('verifyUri', $result);
		$this->decision = $this->__parse_is_set('decision', $result);
		$this->score = $this->__parse_is_set('score', $models[0]);
		$this->level = $this->__parse_is_set('level', $strategies[0]);
		
		//获取策略信息并转为json串：方便存入数据库【因为上面$strategies使用，当前行代码位置不能往前放置】
		$this->strategies = $strategies ? json_encode($strategies):'';

		$yidun_date = [
			'event_id' =>$this->__parse_is_set('id', $result),
			'event_code' =>'SCENE_LOAN',
			'decision' =>$this->__parse_is_set('decision', $result),
			'verifyId' =>$this->__parse_is_set('verifyId', $result),
			'verifyUri' =>$this->__parse_is_set('verifyUri', $result),
			'score' =>$this->score,
			'level' =>$this->level,
			'strategies' =>$this->strategies,
			'user_name' =>$this->__parse_is_set('user_name', $data['key_value_map']),
			'user_id' =>$this->__parse_is_set('user_id', $data['key_value_map']),
			'mobile' => $this->__parse_is_set('mobile', $data['key_value_map']),
			'email' => $this->__parse_is_set('email', $data['key_value_map']),
			'ip' => $this->__parse_is_set('ip', $data['key_value_map']),
			'platform' => $this->__parse_is_set('platform',$this->__parse_is_set('event', $this->__parse_is_set('knowledge', $result, false),false)),
			'user_agent' => $this->__parse_is_set('user_agent', $data['key_value_map']),
			'cert_no' => $this->__parse_is_set('cert_no', $data['key_value_map']),
			'address_id' =>$this->address_id,
			'create_time' =>time(),
		];

		//保存记录
		$yidun_server = \hd_load::getInstance()->service('yidun/yidun');
		$this->yidun_id = $yidun_server->create($yidun_date);
		//蚁盾接口返回值有无问题，目前按照所有都是无风险处理
		$this->flag = true;
		
		//设置缓存数据
		$redis_data = [
			'verify_id' =>$this->verify_id,
			'verify_uri' =>$this->verify_uri,
			'decision' =>$this->decision,
			'score' =>$this->score,
			'level' =>$this->level,
			'strategies' =>$this->strategies,
			'yidun_id' =>$this->yidun_id,
			'hash' => $redis_hash,
		];
		$redis->set($redis_key, json_encode($redis_data),3600);
		
		return true;

		//-+--------------------------------------------------------------------
		//-| 验证蚁盾请求结果
		//-+--------------------------------------------------------------------
		//验证返回值的是否获取成功
		if( $result && isset($result['code']) && $result['code'] == 'OK' ) {
			//判断风险类型
			if( $this->__parse_is_set('decision',$result) == self::RISK_ACCEPT ) {
				$this->flag = true;//无风险
			}elseif( $this->__parse_is_set('decision',$result) == self::RISK_REJECT ){
				$this->flag = false;//有风险
			}elseif( $this->__parse_is_set('decision',$result) == self::RISK_VALIDATE ){
				//根据业务模型处理风险
				$this->__deal_risk($result);
			}else{
				$this->get_order_creater()->set_error('用户的风险类型不符合设置规则');
				$this->flag = false;//未查询到结果或者风险类型不符合规则则默认用户存在风险
			}
		}else{
			$this->get_order_creater()->set_error('用户的风险验证信息获取失败');
			$this->flag = false;//未查询到结果或者风险类型不符合规则则默认用户存在风险
		}
	}
	/**
	 * 根据业务规则处理风险
	 * @param type $result
	 */
	private function __deal_risk($result){
		$setting_service = \hd_load::getInstance()->service('admin/setting');
		$setting_info = $setting_service->get();
		$setting_yidun_score = $this->__parse_is_set('yidun_score', $setting_info);
		$setting_yidun_score = $setting_yidun_score ? $setting_yidun_score : 80;
		//判断返回的分值
		if( intval($this->score) >= intval($setting_yidun_score) ) {
			$this->get_order_creater()->set_error('用户的风险分数高于'.$setting_yidun_score .'分');
			$this->flag = false;//有风险
		}else{
			$this->flag = true;//无风险
		}
	}
	/**
	 * 蚁盾验证用户风险接口的传入参数校验
	 * @param array $data
	 * $data = [
	 *		"userName" => '',        //string,用户姓名
	 *		"userId" => '',			 //string,用户ID
	 *		"mobile" => '',			 //string,用户手机号
	 *		"email" => '',			 //string,用户邮箱
	 *		"apdIdToken" => '',		 //string,
	 *		"ip" => '',				 //string,用户ip
	 *		"wifiMac" => '',		 //string,mac地址
	 *		"imei" => '',			 //string,用户imei
	 *		"imsi" => '',			 //string,用户imsi
	 *		"latitude" => '',		 //string,纬度
	 *		"longitude" => '',		 //string,经度
	 *		"platform" => '',		 //string,平台，ios,android,windows
	 *		"userAgent" => '',		 //string,userAgent
	 *		"certNo" => '',			 //string,身份证号
	 * ]
	 * @return boolen true校验无误；false参数有误【错误详情用全局函数get_error()获取】
	 */
	private function __parse_data(&$data){
		//初始化最终数据的数组
		$data_arr = [
			'user_name' => $this->__parse_is_set('user_name', $data),
			'user_id' => $this->__parse_is_set('user_id', $data),
			'mobile' => $this->__parse_is_set('mobile', $data),
			'email' => $this->__parse_is_set('email', $data),
			'ip' => $this->__parse_is_set('ip', $data),
			'platform' => $this->__parse_is_set('platform', $data),
			'user_agent' => $this->__parse_is_set('user_agent', $data),
			'cert_no' => $this->__parse_is_set('cert_no', $data),
			'user_reg_time' => $this->__parse_is_set('user_reg_time', $data),
			'receive_name' => $this->__parse_is_set('receive_name', $data),			//收货人
			'receive_mobile' => $this->__parse_is_set('receive_mobile ', $data),	//收货人手机号
			'receive_address' => $this->__parse_is_set('receive_address', $data),	//收货人地址
			'receive_county' => $this->__parse_is_set('receive_county', $data),		//收货人县或区信息
			'receive_city' => $this->__parse_is_set('receive_city', $data),			//收货人城市信息
			'receive_province' => $this->__parse_is_set('receive_province', $data),	//收货人省份信息
			'gmt_occur' => date('Y-m-d H:i:s')
		];
		//验证参数
		if( !$data_arr['user_name'] || !$data_arr['user_id'] || !$data_arr['cert_no'] || !$data_arr['mobile'] || !$data_arr['gmt_occur'] ) {
			set_error('用户参数获取失败');
			return false;
		}
		$data = [
			'event_code' => 'SCENE_LOAN',//场景贷款事件，目前只用此类事件不考虑其它
			'key_value_map' => $data_arr
		];
		return true;
	}
	/**
	 * 验证参数是否存在，不存在返回空，存在则转换为字符串
	 * @param string $key 验证的key值
	 * @param string $value 验证的值
	 * @param boolen $flag 是否转为字符串
	 */
	private function __parse_is_set($key,$value,$flag=true){
		if( isset($value[$key]) ) {
			if( $flag ) {
				return strval($value[$key]);
			}
			return $value[$key];
		}
		return '';
	}
}