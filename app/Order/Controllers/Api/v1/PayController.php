<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Service\OrderCleaning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\Common\LogApi;

/**
 * 支付控制器
 */
class PayController extends Controller
{

    public function __construct()
    {
    }
	
	// 测试 代扣签约
	public function testWithholdSign(){
		
		$business_type = 1; 
		$business_no = 'FA52123851726694';
		try {
			// 查询
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);
			
			$step = $pay->getCurrentStep();
			// echo '当前阶段：'.$step."\n";
			
			$_params = [
				'name'			=> '测试-'.$step,					//【必选】string 交易名称
				'front_url'		=> env('APP_URL').'/order/pay/testWithholdSignFront',	//【必选】string 前端回跳地址
			];
			$url_info = $pay->getCurrentUrl( $_params );
//			var_dump( $_params, $url_info );exit;
			header( 'Location: '.$url_info['url'] );

		} catch (\App\Lib\NotFoundException $exc) {
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			echo $exc->getTraceAsString();
		}
	}
		// 测试 前端回跳
	public function testWithholdSignFront(){
		LogApi::info('代扣签约同步通知', $_GET);
		var_dump( $_GET );exit;
	}
	
	// 测试 代扣解约
	public function testWithholdUnsign(){
		
		$info = \App\Lib\Payment\CommonWithholdingApi::unSign([
    		'user_id'		=> '5', //租机平台用户ID
    		'agreement_no'	=> '30A51306522705601', //支付平台签约协议号
    		'out_agreement_no'	=> '12345678905', //业务平台签约协议号
    		'back_url'		=> env('APP_URL').'/order/pay/withholdUnsignNotify', //后端回调地址
		]);
		var_dump( $info );exit;
	}
		
	
	// 测试 退款
	public function testRefund(){
		$info = \App\Lib\Payment\CommonRefundApi::apply([
    		'name'			=> '测试退款',			//交易名称
    		'out_refund_no' => \createNo(1),		//业务系统退款码
    		'payment_no'	=> '10A52223623034601', //支付系统支付码
    		'amount'		=> 1, //支付金额；单位：分
			'refund_back_url'		=> env('APP_URL').'/order/pay/refundNotify',	//【必选】string //退款回调URL
		]);
		var_dump( $info );exit;
	}
	
	/**
	 * 
	 * @param array $_POST
	 * [
	 *		'refund_no'		=> '',	//【必选】string 支付系统退款编号
	 *		'out_refund_no'	=> '',	//【必选】string 业务系统退款编号
	 *		'status'		=> '',	//【必选】string 支付状态； init：初始化； processing：处理中；success：退款成功；failed：退款失败
	 *		'amount'		=> '',	//【必选】int 交易金额； 单位：分
	 *		'reason'		=> '',	//【必选】stirng 失败原因
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function refundNotify()
	{
		$input = file_get_contents("php://input");
		LogApi::info('退款异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
		
	}
	
	
	// 测试 支付
	public function test(){
		
		
		$business_type = 1; 
		$business_no = 'FA52283402709380';
		$pay = null;
		try {
			// 查询
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);
			// 取消
			$pay->cancel();
			// 恢复
			$pay->resume();

		} catch (\App\Lib\NotFoundException $exc) {

			// 创建支付
			$pay = \App\Order\Modules\Repository\Pay\PayCreater::createPaymentWithholdFundauth([
				'user_id'		=> '5',
				'businessType'	=> $business_type,
				'businessNo'	=> $business_no,
				
				'paymentNo' => \createNo(1),
				'paymentAmount' => '0.01',
				'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
				'paymentFenqi'	=> 0,
				
				'withholdNo' => \createNo(1),
				'withholdChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
				
				'fundauthNo' => \createNo(1),
				'fundauthAmount' => '1.00',
				'fundauthChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
			]);
		} catch (\Exception $exc) {
			exit('error');
		}
		
		try {
			$step = $pay->getCurrentStep();
			// echo '当前阶段：'.$step."\n";
			
			$_params = [
				'name'			=> '测试支付',					//【必选】string 交易名称
				'front_url'		=> env('APP_URL').'/order/pay/testPaymentFront',	//【必选】string 前端回跳地址
			];
			$url_info = $pay->getCurrentUrl( $_params );
			header( 'Location: '.$url_info['url'] ); 
//			var_dump( $url_info );
			
		} catch (\Exception $exc) {
			echo $exc->getMessage()."\n";
			echo $exc->getTraceAsString();
		}
		
	}
	// 测试 支付前端回跳
	public function testPaymentFront(){
		LogApi::info('支付同步通知', $_GET);
		var_dump( $_GET );exit;
	}
	
	
	/**
	 * 支付异步通知处理
	 * @param array $_POST
	 * [
	 *		'payment_no'		=> '',	//【必选】string 支付系统支付编号
	 *		'out_payment_no'	=> '',	//【必选】string 业务系统支付编号
	 *		'status'		=> '',	//【必选】string 支付状态； init：初始化； processing：处理中；success：支付成功；failed：支付失败
	 *		'amount'		=> '',	//【必选】int 交易金额； 单位：分
	 *		'reason'		=> '',	//【必选】stirng 失败原因
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function paymentNotify()
	{
		$input = file_get_contents("php://input");
		LogApi::info('支付异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
//		$params = [
//			'payment_no'	=> '10A52108092865700',
//			'out_payment_no'=> 'FA52108092585030',
//			'status'		=> 'success',
//			'amount'		=> '1',
//		];
		
		try {
			// 校验支付状态
			$status_info = \App\Lib\Payment\CommonPaymentApi::query($params);
			if( $status_info['status'] != 'success' ){// 支付成功
				echo 'payment status not success';exit;
			}
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByPaymentNo( $params['out_payment_no'] );
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo 'payment notice repeated ';exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needPayment() ){
				echo 'payment not need ';exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 支付处理
			$pay->paymentSuccess([
				'out_payment_no' => $params['payment_no'],
				'payment_time' => time(),
			]);
			
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			echo $exc->getMessage();
		}
		
		DB::rollBack();
		exit;
	}
	
	/**
	 * 代扣签约异步通知处理
	 * @param array $_POST
	 * [
	 *		'agreement_no'		=> '',	//【必选】string 支付系统编号
	 *		'out_agreement_no'	=> '',	//【必选】string 业务系统编号
	 *		'user_id'			=> '',	//【必选】string 业务系统用户ID
	 *		'status'			=> '',	//【必选】string 状态； init：初始化； processing：处理中；success：支付成功；failed：支付失败
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function withholdSignNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('代扣签约异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
		try {
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByWithholdNo( $params['out_agreement_no'] );
			
			if( $pay->getUserId() != $params['user_id'] ){
				echo 'notice [user_id] not error';exit;
			}
			
			// 校验状态
			$status_info = \App\Lib\Payment\CommonWithholdingApi::queryAgreement([
				'agreement_no' => $params['agreement_no'],
				'out_agreement_no' => $params['out_agreement_no'],
				'user_id' => $pay->getUserId(),
			]);
			// 签约状态
			if( $status_info['status'] != 'signed' ){// 已签约
				echo 'withhold status not signed';exit;
			}
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo 'withhold notice repeated ';exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needWithhold() ){
				echo 'withhold not need ';exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 代扣签约处理
			$pay->withholdSuccess([
				'out_withhold_no' => $params['agreement_no'],	// 支付系统代扣协议编码
			]);
			
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			echo $exc->getMessage();
		}
		
		DB::rollBack();
		exit;
	}
	/**
	 * 代扣解约 异步通知
	 */
	public function withholdUnsignNotify(){
		
		$input = file_get_contents("php://input");
		LogApi::info('代扣解约异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
	}
	
	
	/**
	 * 预授权冻结异步通知处理
	 * @param array $_POST
	 * [
	 *		'fundauth_no'		=> '',	//【必选】string 支付系统编号
	 *		'out_fundauth_no'	=> '',	//【必选】string 业务系统编号
	 *		'status'		=> '',	//【必选】string 状态； init：初始化； processing：处理中；success：支付成功；failed：支付失败
	 *		'amount'		=> '',	//【必选】int 交易金额； 单位：分
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function fundauthNotify(){
		
		$input = file_get_contents("php://input");
		LogApi::info('预授权冻结异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
		try {
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByFundauthNo( $params['out_fundauth_no'] );
						
			// 校验状态
			$status_info = \App\Lib\Payment\CommonFundAuthApi::queryFundAuthStatus([
				'fundauth_no' => $params['fundauth_no'],
				'out_fundauth_no' => $params['out_fundauth_no'],
				'user_id' => $pay->getUserId(),
			]);
			// 签约状态
			if( $status_info['status'] != 'signed' ){// 已签约
				echo 'withhold status not signed';exit;
			}
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo 'withhold notice repeated ';exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needWithhold() ){
				echo 'withhold not need ';exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 代扣签约处理
			$pay->withholdSuccess([
				'out_withhold_no' => $params['agreement_no'],	// 支付系统代扣协议编码
			]);
			
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			echo $exc->getMessage();
		}
		
		DB::rollBack();
		exit;
		
	}
	public function fundautUnfreezeNotify(){
		
		$input = file_get_contents("php://input");
		LogApi::info('预授权解冻异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
	}
	
	
	//-+------------------------------------------------------------------------

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

//    //支付回调接口
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
        $data =[
            'subject'=>'用户已付款',
            'body'=>'订单编号：'.$order_info['order_no']."联系方式：".$order_info['mobile']." 请联系用户确认租用意向。",
            'address'=>[
                ['address' => EmailConfig::Service_Username]
            ],
        ];

        $send =EmailConfig::system_send_email($data);
        if(!$send){
            Debug::error(Location::L_Trade, "发送邮件失败", $data);
        }
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
     * 订单清算退款回调地址
     * Author: heaven
     * @param Request $request
     */
    public function refundClean(Request $request){

        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'订单清算退款回调地址参数:'.$input);
        $param = json_decode($input,true);
        $rule = [
         'out_refund_no'=>'required', //订单系统退款码
         'refund_no'=>'required', //支付系统退款码
         'status'=>'required',//状态类型
         'reason'=>'required', //错误原因
       ];

        $validateParams = $this->validateParams($rule,$param);
        if ($validateParams['code']!=0) return apiResponse([],$validateParams['code']);
        if ($param['params']['status']!='success'){
            LogApi::info(__METHOD__.'() '.microtime(true).'返回结果:'.$input.'订单清算退款失败');
        }
        //更新查看清算表的状态
        $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['out_refund_no'=>$param['params']['out_refund_no']]);
        if ($orderCleanInfo['code']) {
            LogApi::info(__METHOD__."() ".microtime(true)." 订单清算记录不存在");

        }
            $orderCleanInfo = $orderCleanInfo['data'];
            //查看清算状态是否已支付
            if ($orderCleanInfo['refund_status']==OrderCleaningStatus::refundUnpayed){

                //更新订单清算退款状态
                $orderParam = [
                    'out_refund_no' => $orderCleanInfo['out_refund_no'],
                    'refund_no'     => $param['params']['refund_no'],
                    'refund_status' => OrderCleaningStatus::refundPayd
                ];
                $success = OrderCleaning::upOrderCleanStatus($orderParam);
                if ($success) {
                    //查看其他状态是否完成，如果完成，更新整体清算的状态
                    if ($orderCleanInfo['deposit_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                        $orderCleanInfo['deposit_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                        $orderParam = [
                            'out_refund_no' => $param['params']['out_refund_no'],
                            'status' => OrderCleaningStatus::orderCleaningComplete
                        ];
                        $success = OrderCleaning::upOrderCleanStatus($orderParam);
                        if ($success) {
                            //更新业务系统的状态
                            $success =  OrderCleaning::getBusinessCleanCallback($orderCleanInfo['business_type'], $orderCleanInfo['business_no'], $param['params']['status']);
                        }
                    }
                } else {
                    $this->innerErrMsg();
                    LogApi::info(__METHOD__."() ".microtime(true)." 更新失败");
                }

             } else {

                \Log::debug(__METHOD__ . "() " . microtime(true) . " {$param['params']['out_refund_no']}订单清算退款状态无效");
            }
            $this->innerOkMsg();

        }


    /**
     * 订单清算退押金回调接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unFreezeClean(Request $request)
    {
        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'订单清算退押金回调接口回调参数:'.$input);
        $param = json_decode($input,true);
        $rule = [
            "reason"=>'required',                //类型：String  必有字段  备注：错误原因
            "status"=>'required',                //类型：String  必有字段  备注：init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
            "trade_no"=>'required',                //类型：String  必有字段  备注：支付平台交易码
            "out_trade_no"=>'required',                //类型：String  必有字段  备注：业务系统交易码
            "auth_no"=>'required',                //类型：String  必有字段  备注：支付平台授权
            "user_id"=>'required'                //类型：String  必有字段  备注：用户id
        ];

        $validateParams = $this->validateParams($rule,$param);
        if ($validateParams['code']!=0) return apiResponse([],$validateParams['code']);
        //更新查看清算表的状态
        $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['out_refund_no'=>$param['params']['out_refund_no']]);
        if ($orderCleanInfo['code']) {
            LogApi::info(__METHOD__."() ".microtime(true)." 订单清算记录不存在");

        }
        $orderCleanInfo = $orderCleanInfo['data'];
        //查看退押金状态是否是待退押金状态
        if ($orderCleanInfo['deposit_unfreeze_status']==OrderCleaningStatus::depositUnfreezeStatusUnpayed){

            //更新订单退押金状态
            $orderParam = [
                'out_refund_no' => $orderCleanInfo['out_refund_no'],
                'refund_no'     => $param['params']['refund_no'],
                'deposit_unfreeze_status' => OrderCleaningStatus::depositUnfreezeStatusPayd
            ];
            $success = OrderCleaning::upOrderCleanStatus($orderParam);
            if ($success) {
                //查看其他状态是否完成，如果完成，更新整体清算的状态
                if ($orderCleanInfo['deposit_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                    $orderCleanInfo['deposit_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                    $orderParam = [
                        'out_refund_no' => $param['params']['out_refund_no'],
                        'status' => OrderCleaningStatus::orderCleaningComplete
                    ];
                    $success = OrderCleaning::upOrderCleanStatus($orderParam);
                    if ($success) {
                        //更新业务系统的状态
                        $success =  OrderCleaning::getBusinessCleanCallback($orderCleanInfo['business_type'], $orderCleanInfo['business_no'], $param['params']['status']);
                    }
                }
            } else {
                $this->innerErrMsg();
                LogApi::info(__METHOD__."() ".microtime(true)." 更新失败");
            }

        } else {

            \Log::debug(__METHOD__ . "() " . microtime(true) . " {$param['params']['out_refund_no']}订单清算退款状态无效");
        }
        $this->innerOkMsg();

    }



    /**
     * 订单清算押金转支付回调接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfreezeAndPayClean(Request $request)
    {
        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'订单清算退押金回调接口回调参数:'.$input);
        $param = json_decode($input,true);
        $rule = [
            "reason"=>'required',                //类型：String  必有字段  备注：错误原因
            "status"=>'required',                //类型：String  必有字段  备注：init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
            "trade_no"=>'required',                //类型：String  必有字段  备注：支付平台交易码
            "out_trade_no"=>'required',                //类型：String  必有字段  备注：业务系统交易码
            "auth_no"=>'required',                //类型：String  必有字段  备注：支付平台授权
            "user_id"=>'required'                //类型：String  必有字段  备注：用户id
        ];

        $validateParams = $this->validateParams($rule,$param);
        if ($validateParams['code']!=0) return apiResponse([],$validateParams['code']);
        if ($param['params']['status']!='success'){
            LogApi::info(__METHOD__.'() '.microtime(true).'返回结果:'.$input.'订单清算退款失败');
        }
        //更新查看清算表的状态
        $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['out_refund_no'=>$param['params']['out_refund_no']]);
        if ($orderCleanInfo['code']) {
            LogApi::info(__METHOD__."() ".microtime(true)." 订单清算记录不存在");

        }
        $orderCleanInfo = $orderCleanInfo['data'];
        //查看清算状态是否已支付
        if ($orderCleanInfo['refund_status']==OrderCleaningStatus::refundUnpayed){

            //更新订单清算退款状态
            $orderParam = [
                'out_refund_no' => $orderCleanInfo['out_refund_no'],
                'refund_no'     => $param['params']['refund_no'],
                'refund_status' => OrderCleaningStatus::refundPayd
            ];
            $success = OrderCleaning::upOrderCleanStatus($orderParam);
            if ($success) {
                //查看其他状态是否完成，如果完成，更新整体清算的状态
                if ($orderCleanInfo['deposit_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                    $orderCleanInfo['deposit_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                    $orderParam = [
                        'out_refund_no' => $param['params']['out_refund_no'],
                        'status' => OrderCleaningStatus::orderCleaningComplete
                    ];
                    $success = OrderCleaning::upOrderCleanStatus($orderParam);
                    if ($success) {
                        //更新业务系统的状态
                        $success =  OrderCleaning::getBusinessCleanCallback($orderCleanInfo['business_type'], $orderCleanInfo['business_no'], $param['params']['status']);
                    }
                }
            } else {
                $this->innerErrMsg();
                LogApi::info(__METHOD__."() ".microtime(true)." 更新失败");
            }

        } else {

            \Log::debug(__METHOD__ . "() " . microtime(true) . " {$param['params']['out_refund_no']}订单清算退款状态无效");
        }
        $this->innerOkMsg();

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
            //修改分期状态
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




}
