<?php
namespace App\Lib\AlipaySdk\sdk;
/*
 * 芝麻扣款 取消订单 关闭订单 接口
 */


require_once __DIR__ . '/aop/request/ZhimaMerchantOrderCreditPayRequest.php';
/**
 * 芝麻扣款 取消订单 关闭订单 发送请求
 *
 * @author
 */
class CommonMiniApi extends BaseApi {

	public function __construct($appid) {
		$this->appid = $appid;
		parent::__construct($appid);
	}

	private $result;

	//請求返回值
	public function getResult(){
		return $this->result;
	}

	//錯誤返回值
	public function getError(){
		return $this->error;
	}

	/**
	 * 芝麻扣款 取消订单 关闭订单
	 */
	public function withholdingCancelClose( $params ) {
		$params = filter_array($params, [
			'order_operate_type' => 'required',		    // 【必须】请求类型
			'out_order_no' => 'required',		    // 【必须】租机商户订单号
			'zm_order_no' => 'required',		    // 【必须】芝麻交易号
			'out_trans_no' => 'required',	    // 【必须】支付宝交易码
			'pay_amount' => 'required', // 【必须】支付金额，元
			'remark' => 'required',	    // 【可选】取消原因
		]);
		//支付宝交易号，和商户订单号二选一
		$biz_content['order_operate_type'] = $params['order_operate_type'];	// 请求类型
		$biz_content['out_order_no'] = $params['out_order_no'];	// 商户交易号
		$biz_content['zm_order_no'] = $params['zm_order_no'];	// 芝麻交易号
		if(isset($params['out_trans_no'])){
			$biz_content['out_trans_no'] = $params['out_trans_no'];
		}
		if(isset($params['pay_amount'])){
			$biz_content['pay_amount'] = $params['pay_amount'];
		}
		if(isset($params['remark'])){
			$biz_content['remark'] = $params['remark'];
		}
		$request = new \ZhimaMerchantOrderCreditPayRequest();
		$request->setBizContent (json_encode($biz_content) );
		//入库请求信息
		$result = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::add($biz_content);
		if( !$result ){
			\App\Lib\Common\LogApi::debug('小程序请求记录失败',$result);
		}
		$response = $this->execute($request);
		echo $response;
		$result = json_decode(json_encode($response),true);
		print_r($result);
		$debug_data = [
			'request' => $biz_content,
			'response' => $response,
		];
		if( !isset($result['zhima_merchant_order_credit_pay_response']) ){
			$this->error = '芝麻扣款 取消订单 关闭订单 接口，返回值错误';
			\App\Lib\Common\LogApi::notify('芝麻接口，返回值错误',$debug_data);
			return false;
		}
		if( $result['zhima_merchant_order_credit_pay_response']['code']!=10000 ){
			$msg = $result['zhima_merchant_order_credit_pay_response']['sub_msg'];
			$this->error = $result['zhima_merchant_order_credit_pay_response']['sub_code'].$result['zhima_merchant_order_credit_pay_response']['sub_msg'];
			\App\Lib\Common\LogApi::notify('芝麻接口：'.$msg,$debug_data);
			return false;
		}
		$this->result = $result;
		return true;
	}

	/**
	 * 信用套餐产品订单确认接口（查询芝麻订单信息）
	 * @param $params
	 * @return bool|mixed
	 */
	public function orderConfirm( $params ){

		$param = filter_array($params, [
			'order_no' => 'required',		    // 【必须】芝麻信用订单号
			'transaction_id' => 'required'		// 【必须】一笔请求的唯一标志
		]);

		if( count($param)!=2 ){
			$this->error = '信用套餐产品订单确认接口业务参数错误';
			return false;
		}

		//请求业务参数
		$biz_content['order_no'] = $param['order_no'];
		$biz_content['transaction_id'] = $param['transaction_id'];

		$request = new \ZhimaMerchantOrderConfirmRequest();
		$request->setBizContent(json_encode($biz_content));
		$result = $this->execute ( $request);
		if(!$result){
			$this->error = $this->getError();
			return false;
		}
		$params['appid'] = $this->appid;
		$debug_data = [
			'request' => $biz_content,
			'response' => json_decode(json_encode($result),true),
		];
		\App\Lib\Common\LogApi::notify('芝麻接口请求默认返回值',$debug_data);
//		$this->result = [
//			'name'=>'张三',
//			'cert_no'=>'110101200010012568',
//			'mobile'=>'18820881688',
//			'house'=>'北京 北京 顺义 西湖区文三路168号',
//			'zm_grade'=>'Z3',
//			'credit_amount'=>'1500',
//			'user_id'=>'2018032002411058',
//			'channel_id'=>'app',
//			'zm_risk'=>'Y',
//			'zm_face'=>'Y',
//		];
//		return true;
		print_r($result);die;
		if( !isset($result['zhima_merchant_order_confirm_response']) ){
			$this->error = '芝麻扣款 取消订单 关闭订单 接口，返回值错误';
			\App\Lib\Common\LogApi::notify('芝麻接口，返回值错误',$debug_data);
			return false;
		}
		if( $result['zhima_merchant_order_confirm_response']['code']!=10000 ){
			$this->error = $result['zhima_merchant_order_confirm_response']['sub_code'].$result['zhima_merchant_order_confirm_response']['sub_msg'];
			\App\Lib\Common\LogApi::notify('芝麻接口：返回值错误',$debug_data);
			return false;
		}
		$this->result = $result;
		//查询成功记录表
		$res = \App\Order\Modules\Repository\OrderMiniRepository::add(array_merge($params,$this->result));
		if( !$res ){
			\App\Lib\Common\LogApi::debug('小程序请求记录失败',$res);
		}
		return true;
	}

}
