<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;
use App\Lib\Common\LogApi;

/**
 * 支付控制器
 */
class PayController extends Controller
{
    protected $orderTrade;

    public function __construct(Service\OrderTrade $orderTrade)
    {
        $this->orderTrade = $orderTrade;
    }

    /**
	 * 通用支付入口，获取支付链接地址
	 * @param Request $request
	 * [
	 *		'payment_no' => '',	// 支付码
	 * ]
	 * @return type
	 */
    public function getPaymentUrl(Request $request){

		$params = $request->all()['params'];
		// 
		if( empty($params['payment_no']) ){
			return apiResponse( [],ApiStatus::CODE_30900,'参数错误[payment_no]');
		}
		$payment_no = $params['payment_no'];
		
		$payModel = \App\Order\Models\OrderPayModel::where('payment_no','=',$payment_no)->first();
		if( !$payModel ){
			return apiResponse( [],ApiStatus::CODE_30900,'支付单未识别');
		}
		$pay = new \App\Order\Modules\Repository\Pay\Pay( $payModel->toArray() );
		LogApi::debug('[直接支付环节]支付链接',$pay);
		
		// 是否可以支付
		if( !$pay->needPayment() ){
			return apiResponse( [],ApiStatus::CODE_30900,'禁止支付');
		}
		
		// 创建url地址
		$_params = [
	 		'out_payment_no' => $pay->getPaymentNo(),
			'payment_channel' => $pay->getPaymentChannel(),
	 		'payment_amount' => $pay->getPaymentAmount()*100, // 元转换成分
	 		'fenqi' => $pay->getPaymentFenqi(),
	 		'name' => 'Xxxx',
	 		'back_url' => 'https://abc.com',
	 		'front_url' => 'https://abc.com',
	 		'user_id' => '5',
		];
		$data = \App\Lib\Payment\CommonPaymentApi::pageUrl( $_params );
		
		if( !$data ){
			LogApi::error(ApiStatus::CODE_30901,[
				'msg' => \App\Lib\Payment\AlipayApi::getError(),
				'params' => $_params,
			]);
			return apiResponse( [], ApiStatus::CODE_30901,'服务器忙，稍候重试');
		}
		
        return apiResponse( $data,ApiStatus::CODE_0);
    }
	
	
	
    //支付回调接口
    public function notify(Request $request){
        $params =$request->input();
        $params=[
            'gmt_create' => '2017-11-28 02:58:20',//支付时间
            'trade_status' => 'TRADE_SUCCESS',//支付状态
            'out_trade_no' => '2017112800014',//订单生成支付交易码
            'trade_no' => '2017112821001004700573432203',//返回流水号
        ];
        var_dump("成功更新订单支付状态");die;
        //发送短信
        //发送支付宝推送消息
        //发送邮件 -----begin
//        $data =[
//            'subject'=>'用户已付款',
//            'body'=>'订单编号：'.$order_info['order_no']."联系方式：".$order_info['mobile']." 请联系用户确认租用意向。",
//            'address'=>[
//                ['address' => EmailConfig::Service_Username]
//            ],
//        ];
//
//        $send =EmailConfig::system_send_email($data);
//        if(!$send){
//            Debug::error(Location::L_Trade, "发送邮件失败", $data);
//        }
//
//        //发送邮件------end

    }

	/**
	 * 签约代扣回调接口
	 * [
	 *      'reason'            => '' // 错误原因
	 *      'status'            => '' // 回调状态 0成功 1失败
	 *      'out_agreement_no'  => '' // 支付平台签约协议号
	 *      'agreement_no'      => '' // 订单系统签约协议号
	 *      'user_id'           => '' // 用户id
	 * ]
	 */
	public function sign_notify(Request $request){
		$request    = $request->all();
		$params     = $request['params'];

		$rules = [
			'reason'            => 'required',
			'status'            => 'required|int',
			'out_agreement_no'  => 'required',
			'agreement_no'      => 'required',
			'user_id'           => 'required|int',
		];
		$validateParams = $this->validateParams($rules,$params);
		if ($validateParams['code'] != 0) {
			return apiResponse([],$validateParams['code']);
		}
		$params = $params['params'];

		//成功 则保存数据
		if($params['status'] == ApiStatus::CODE_0){

			$data  = [
				'withhold_no'       => $params['agreement_no'],
				'out_withhold_no'   => $params['out_agreement_no'],
				'user_id'           => $params['user_id'],
			];
			$withhold = \App\Order\Modules\Service\OrderPayWithhold::create_withhold($data);
			if(!$withhold){
				return apiResponse([],ApiStatus::CODE_71001, "异常错误");
			}
			return apiResponse([],ApiStatus::CODE_0, "操作成功");
		}
	}

	/**
	 * 解约代扣回调接口
	 */
	public function unsign_notify(Request $request){
		$request    = $request->all();
		$params     = $request['params'];

		$rules = [
			'reason'            => 'required',
			'status'            => 'required|int',
			'out_agreement_no'  => 'required',
			'agreement_no'      => 'required',
			'user_id'           => 'required|int',
		];
		$validateParams = $this->validateParams($rules,$params);
		if ($validateParams['code'] != 0) {
			return apiResponse([],$validateParams['code']);
		}
		$params = $params['params'];
		//成功 则保存数据
		if($params['status'] == ApiStatus::CODE_0){

			$userId     = $params['user_id'];
			$withhold   = \App\Order\Modules\Service\OrderPayWithhold::unsign_withhold($userId);

			if($withhold !== true){
				return apiResponse([],ApiStatus::CODE_71001, "异常错误");
			}

			return apiResponse([],ApiStatus::CODE_0, "操作成功");
		}

	}
	/**
	 * 代扣扣款回调
	 * @$request array
	 */
	public function createpayNotify(Request $request){
		$request    = $request->all();
		$params     = $request['params'];

		$rules = [
			'reason'            => 'required',
			'status'            => 'required|int',
			'out_agreement_no'  => 'required',
			'agreement_no'      => 'required',
			'out_trade_no'      => 'required',
			'trade_no'          => 'required',
		];
		$validateParams = $this->validateParams($rules,$params);
		if ($validateParams['code'] != 0) {
			return apiResponse([],$validateParams['code']);
		}
		$params = $params['params'];
		//成功 则保存数据
		if($params['status'] == ApiStatus::CODE_0){

			// 修改分期状态
			$prepayment_data =[
				'trade_no'      => $params['trade_no'],
				'status'        => \App\Order\Modules\Inc\OrderInstalmentStatus::SUCCESS,
				'payment_time'  => time(),
				'update_time'   => time(),
			];
			$b = \App\Order\Modules\Service\OrderInstalment::save(['trade_no'=>$params['trade_no']],$prepayment_data);
			if(!$b){
				return apiResponse([],ApiStatus::CODE_71001, "异常错误");
			}
			return apiResponse([],ApiStatus::CODE_0, "操作成功");
		}
	}

	/**
	 * 提前还款异步回调
	 * @param Request $request
	 */
	public function repaymentNotify(Request $request){
		$request    = $request->all();
		$params     = $request['params'];

		$rules = [
			'payment_no'    => 'required',
			'out_no'        => 'required',
			'status'        => 'required|int',
			'reason'        => 'required',
		];
		$validateParams = $this->validateParams($rules,$params);
		if ($validateParams['code'] != 0) {
			return apiResponse([],$validateParams['code']);
		}
		$params = $params['params'];
		//成功 则保存数据
		if($params['status'] == ApiStatus::CODE_0){

			//修改支付单数据





			// 修改分期状态
//			$prepayment_data =[
//				'trade_no'      => $params['trade_no'],
//				'status'        => \App\Order\Modules\Inc\OrderInstalmentStatus::SUCCESS,
//				'payment_time'  => time(),
//				'update_time'   => time(),
//			];
//			$b = \App\Order\Modules\Service\OrderInstalment::save(['trade_no'=>$params['trade_no']],$prepayment_data);
//			if(!$b){
//				return apiResponse([],ApiStatus::CODE_71001, "异常错误");
//			}
			return apiResponse([],ApiStatus::CODE_0, "操作成功");
		}
	}


}
