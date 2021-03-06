<?php

namespace App\Order\Controllers\Api\v1;
use App\Activity\Models\ActivityDestine;
use App\Activity\Modules\Inc\DestineStatus;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Order\Modules\Repository\Pay\Channel;
use App\Order\Modules\Service\OrderCleaning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\Common\LogApi;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


/**
 * 支付控制器
 */
class PayController extends Controller
{

    public function __construct()
    {
    }


    /**
     * 代扣+预授权。。支付单跳转url
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function payment(Request $request){
//		\App\Lib\Payment\CommonWithholdingApi::unSign([
//			'user_id' => 0,
//			'agreement_no'=>'30A53164270253292',
//			'out_agreement_no'=>'WPA53164269848775',
//			'back_url' => 'zuji-order.com',
//		]);exit;
		
        $params =$request->all();
		$auth_token = $params['auth_token'];
		
        $rules = [
            'callback_url'  => 'required',
            'order_no'  => 'required',
            'pay_channel_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if (empty($validateParams) || $validateParams['code']!=0) {
            return apiResponse([],$validateParams['code']);
        }

        $ip= isset($params['userinfo']['ip'])?$params['userinfo']['ip']:'';
        // 扩展参数
        $params =$params['params'];
        $extended_params= isset($params['extended_params'])?$params['extended_params']:[];

		LogApi::id($params['order_no']);
		
		if( isset($extended_params['wechat_params']['trade_type']) 
				&& ($extended_params['wechat_params']['trade_type']=='JSAPI'
						|| $extended_params['wechat_params']['trade_type']=='MINI' ) ){
			$_key = 'wechat_openid_'.$auth_token;
			$openid = \Illuminate\Support\Facades\Redis::get($_key);
			if( $openid ){
				$extended_params['wechat_params']['openid'] = trim($openid,'"');
			}
		}
		
//			LogApi::info('获取支付的url', ['url'=> json_decode(json_encode($paymentUrl),true),'params'=>$params]);
//			LogApi::info('获取支付的params', ['params'=>$params]);
		//-+--------------------------------------------------------------------
		// | 查询支付单，查询失败则创建
		//-+--------------------------------------------------------------------
		try{
			//验证是否已经创建过，创建成功，返回true,未创建会抛出异常进行创建
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,$params['order_no'] );
		} catch (\App\Lib\NotFoundException $e) {
            return apiResponse([],ApiStatus::CODE_50004,$e->getMessage());
		} 
		
		//-+--------------------------------------------------------------------
		// | 获取并返回url
		//-+--------------------------------------------------------------------
		try{
			$step = $pay->getCurrentStep();
			if( $step == 'payment' ){
				$name = '【拿趣用】订单:' . $params['order_no'] . '-支付';
			}elseif( $step == 'withhold_sign' ){
				$name = '【拿趣用】订单:' . $params['order_no'] . '-签约代扣';
			}elseif( $step == 'fundauth' ){
				$name = '【拿趣用】订单:' . $params['order_no'] . '-预授权';
			}else{
				$name = '【拿趣用】订单:' . $params['order_no'] . '-交易';
			}
			$paymentUrl = $pay->getCurrentUrl($params['pay_channel_id'], [
					'name'=> $name,
					'front_url' => $params['callback_url'],
					'ip'=>$ip,
					'extended_params' => $extended_params,// 扩展参数
			]);
			return apiResponse($paymentUrl,ApiStatus::CODE_0);
		} catch (\Exception $exs) {
			LogApi::error('获取支付链接地址错误',$exs);
            return apiResponse([],ApiStatus::CODE_50004,$exs->getMessage());
		}
    }
	
	/**
	 * 支付异步通知处理
	 * @param array $_POST
	 * [
	 *		'channel'		=> '',  //【必选】int 支付渠道
	 *		'payment_no'	=> '',	//【必选】string 支付系统支付编号
	 *		'out_payment_no'=> '',	//【必选】string 业务系统支付编号
	 *		'status'		=> '',	//【必选】string 支付状态； init：初始化； processing：处理中；success：支付成功；failed：支付失败
	 *		'amount'		=> '',	//【必选】int 交易金额； 单位：分
	 *		'reason'		=> '',	//【必选】stirng 失败原因
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function paymentNotify()
	{
		$input = file_get_contents("php://input");
		LogApi::setSource('payment_notify');
		$params = json_decode($input,true);

		LogApi::id($params['out_payment_no']??'');
		LogApi::info('支付异步通知', $input);
//        if(isset($params['sign'])){
//            //数据签名验证
//            $sign = $params['sign'];
//            unset($params['sign']);
//            ksort($params);
//            $b = \App\Lib\AlipaySdk\sdk\aop\AopClient::verifySign(http_build_query($params),$sign);
//            if(!$b){
//                echo json_encode([
//                    'status' => 'error',
//                    'msg' => 'Signature error ',
//                ]);exit;
//            }
//        }else{
//            echo json_encode([
//                'status' => 'error',
//                'msg' => 'notice Sign is null',
//            ]);exit;
//        }
		if( is_null($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data not array',
			]);exit;
		}
				
		try {
			// 校验支付状态
			$status_info = \App\Lib\Payment\CommonPaymentApi::query($params);
			if( $status_info['status'] != 'success' ){// 未支付成功
				echo json_encode([
					'status' => 'error',
					'msg' => 'payment status not success',
				]);exit;
			}
			
			// 开启事务
			DB::beginTransaction();
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByPaymentNo( $params['out_payment_no'], true );
			
			if( $pay->isSuccess() ){// 已经支付成功
				LogApi::debug('支付重复通知，忽略');
				DB::rollBack();
				echo json_encode([
					'status' => 'ok',
					'msg' => 'payment notice repeated',
				]);exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needPayment() ){
				LogApi::debug('支付环节状态禁止');
				DB::rollBack();
				echo json_encode([
					'status' => 'error',
					'msg' => 'payment not need',
				]);exit;
			}
			
			// 支付处理
			$pay->paymentSuccess([
				'out_payment_no' => $params['payment_no'],
				'payment_amount'	=> sprintf('%0.2f',$params['amount']/100),	// 支付金额；单位元
				'payment_channel'	=> $params['channel'],	// 支付渠道
				'payment_time' => time(),
			]);
			LogApi::debug('支付通知处理成功');
			// 提交事务
            DB::commit();	
			LogApi::debug('事务提交成功');
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			LogApi::error('支付通知处理失败',$exc);
			DB::rollBack();
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
		} catch (\Exception $exc) {
			LogApi::error('支付通知处理失败',$exc);
			DB::rollBack();
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
		}
		LogApi::error('支付通知处理失败');
		DB::rollBack();
		exit;
	}
	
	/**
	 * 代扣签约异步通知处理
	 * @param array $_POST
	 * [
	 *		'channel'			=> '',	//【必选】int 支付渠道
	 *		'agreement_no'		=> '',	//【必选】string 支付系统编号
	 *		'out_agreement_no'	=> '',	//【必选】string 业务系统编号
	 *		'user_id'			=> '',	//【必选】string 业务系统用户ID
	 *		'status'			=> '',	//【必选】string 状态； init：初始化； processing：处理中；signed：签约成功；failed：支付失败
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function withholdSignNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('代扣签约异步通知', $input);
		
		$params = json_decode($input,true);

//        //数据签名验证
//        $sign = $params['sign'];
//        unset($params['sign']);
//        ksort($params);
//        $b = \App\Lib\AlipaySdk\sdk\aop\AopClient::verifySign(http_build_query($params),$sign);
//        if(!$b){
//            echo json_encode([
//                'status' => 'error',
//                'msg' => 'Signature error ',
//            ]);exit;
//        }

		if( is_null($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data not array',
			]);exit;
		}
		
		try {
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByWithholdNo( $params['out_agreement_no'] );
			
			if( $pay->getUserId() != $params['user_id'] ){
				echo json_encode([
					'status' => 'error',
					'msg' => 'notice [user_id] error',
				]);exit;
			}
			
			// 校验状态
			$status_info = \App\Lib\Payment\CommonWithholdingApi::queryAgreement([
				'agreement_no' => $params['agreement_no'],
				'out_agreement_no' => $params['out_agreement_no'],
				'user_id' => $pay->getUserId(),
			]);
			// 签约状态
			if( $status_info['status'] != 'signed' ){// 已签约
				echo json_encode([
					'status' => 'error',
					'msg' => 'withhold status not signed',
				]);exit;
			}
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo json_encode([
					'status' => 'ok',
					'msg' => 'withhold notice repeated',
				]);exit;
			}
			
			// 判断是否需要支付
			if( !$pay->needWithhold() ){
				echo json_encode([
					'status' => 'ok',
					'msg' => 'withhold notice repeated',
				]);exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 代扣签约处理
			$pay->withholdSuccess([
				'out_withhold_no' => $params['agreement_no'],	// 支付系统代扣协议编码
				'withhold_channel' => $params['channel'],
			]);
						
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			LogApi::type('data-error')::error('数据未找到',$exc);
			DB::rollBack();
			echo json_encode([
				'status' => 'ok',
				'msg' => $exc->getMessage(),
			]);exit;
		} catch (\Exception $exc) {
			LogApi::error('代扣签约通知处理异常',$exc);
			DB::rollBack();
			echo json_encode([
				'status' => 'ok',
				'msg' => $exc->getMessage(),
			]);exit;
		}
		
		exit;
	}
	/**
	 * 代扣解约 异步通知
	 * @param array $_POST
	 * [
	 *      'reason'            => '', 【必须】 String 错误原因
	 *      'status'            => '', 【必须】 int：success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
	 *      'agreement_no'      => '', 【必须】 String 支付平台签约协议号
	 *      'out_agreement_no'  => '', 【必须】 String 业务系统签约协议号
	 *      'user_id'           => '', 【必须】 int 用户ID
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function withholdUnsignNotify()
	{
		$input = file_get_contents("php://input");
		LogApi::setSource('withhold_unsign_notify');
		LogApi::info('代扣解约异步通知', $input);
		
		$params = json_decode($input,true);
        //数据签名验证
//        $sign = $params['sign'];
//        unset($params['sign']);
//        ksort($params);
//        $b = \App\Lib\AlipaySdk\sdk\aop\AopClient::verifySign(http_build_query($params),$sign);
//        if(!$b){
//            echo json_encode([
//                'status' => 'error',
//                'msg' => 'Signature error ',
//            ]);exit;
//        }
		if( is_null($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data not array',
			]);exit;
		}

		$rules = [
			'reason'            => 'required',
			'status'            => 'required',
			'agreement_no'      => 'required',
			'out_agreement_no'  => 'required',
//			'user_id'           => 'required',
		];
		// 参数过滤
		$validateParams = $this->validateParams($rules,$params);
		if ($validateParams['code'] != 0) {
			
			echo json_encode([
				'status' => 'error',
				'msg' => 'params error '.$validateParams['msg'],
			]);exit;
		}

		// 解约成功 修改协议表
		if($params['status'] == "success"){
			try{
				// 查询用户协议
				$withhold = \App\Order\Modules\Repository\Pay\WithholdQuery::getByWithholdNo( $params['out_agreement_no'] );
				$b = $withhold->unsignSuccess();
				if( $b ){
					echo json_encode([
						'status' => 'ok',
						'msg' => '成功',
					]);exit;
				}else{
					echo json_encode([
						'status' => 'error',
						'msg' => '解约失败',
					]);exit;
				}
				
			} catch(\Exception $exc){

				echo json_encode([
					'status' => 'error',
					'msg' => $exc->getMessage(),
				]);exit;
			}
		}

		echo "SUCCESS";
	}
	
	
	/**
	 * 预授权冻结异步通知处理
	 * @param array $_POST
	 * [
	 *		'channel'			=> '',	//【必选】int 支付渠道
	 *		'fundauth_no'		=> '',	//【必选】string 支付系统编号
	 *		'out_fundauth_no'	=> '',	//【必选】string 业务系统编号
	 *		'status'		=> '',	//【必选】string 状态； init：初始化； processing：处理中；success：支付成功；failed：支付失败
	 *		'total_freeze_amount'		=> '',	//【必选】int 交易金额； 单位：分
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function fundauthNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('预授权冻结异步通知', $input);
		
		$params = json_decode($input,true);
//        //数据签名验证
//        $sign = $params['sign'];
//        unset($params['sign']);
//        ksort($params);
//        $b = \App\Lib\AlipaySdk\sdk\aop\AopClient::verifySign(http_build_query($params),$sign);
//        if(!$b){
//            echo json_encode([
//                'status' => 'error',
//                'msg' => 'Signature error ',
//            ]);exit;
//        }
		if( is_null($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data not array',
			]);exit;
		}
		
		// 提交事务
		DB::beginTransaction();
		try {
			
			// 查询本地预授权的支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByFundauthNo( $params['out_fundauth_no'] );
						
			// 校验状态
			$status_info = \App\Lib\Payment\CommonFundAuthApi::queryFundAuthStatus([
				'fundauth_no' => $params['fundauth_no'],
				'out_fundauth_no' => $params['out_fundauth_no'],
				'user_id' => $pay->getUserId(),
			]);
			// 状态
			if( $status_info['status'] != 'success' ){// 未授权
				DB::rollBack();
				echo json_encode([
					'status' => 'error',
					'msg' => 'fundauth status not success',
				]);exit;
			}
			
			if( $pay->isSuccess() ){// 已经支付成功
				DB::rollBack();
				echo json_encode([
					'status' => 'ok',
					'msg' => 'fundauth notice repeated',
				]);exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needFundauth() ){
				DB::rollBack();
				echo json_encode([
					'status' => 'error',
					'msg' => 'fundauth not need',
				]);exit;
			}
			
			// 代扣签约处理
			$pay->fundauthSuccess([
				'out_fundauth_no' => $params['fundauth_no'],	// 支付系统资金预授权编码
				'total_amount' => sprintf('%0.2f',$params['total_freeze_amount']/100),
				'fundauth_channel' => $params['channel'],
			]);
			
			// 提交事务
            DB::commit();	
			echo json_encode([
				'status' => 'ok',
				'msg' => 'ok',
			]);exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			LogApi::type('data-error')::error('数据未找到',$exc);
			DB::rollBack();
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
		} catch (\Exception $exc) {
			LogApi::error('预授权通知处理异常',$exc);
			DB::rollBack();
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
		}
		
		exit;
		
	}
	
	/**
	 * 
	 */
	public function fundautUnfreezeNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('预授权解冻异步通知', $input);
		
		$params = json_decode($input,true);
        //数据签名验证
//        $sign = $params['sign'];
//        unset($params['sign']);
//        ksort($params);
//        $b = \App\Lib\AlipaySdk\sdk\aop\AopClient::verifySign(http_build_query($params),$sign);
//        if(!$b){
//            echo json_encode([
//                'status' => 'error',
//                'msg' => 'Signature error ',
//            ]);exit;
//        }
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
	}

	/**
	 * 资金预授权转支付通知
	 * Author: heaven
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function fundauthToPayNotify(){
		$input = file_get_contents("php://input");
		LogApi::info('[fundauthToPayNotify]分期转支付回调接口回调参数:'.$input);

		$params = json_decode($input,true);

		if( is_null($params) ){
			LogApi::info('[fundauthToPayNotify]预授权转支付回调逻辑参数为空', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			LogApi::info('[fundauthToPayNotify]预授权转支付回调逻辑参数不是数组', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is array',
			]);exit;
		}

		try{
			// 开启事务
			DB::beginTransaction();

			$b = \App\Order\Modules\Repository\Order\Instalment::paySuccess($params);

			LogApi::info('[fundauthToPayNotify]进入分期预授权转支付回调逻辑：分期更新支付状态和支付时间，返回的结果', $b);
			if( $b ){
				// 提交事务
				DB::commit();
				echo json_encode([
					'status' => 'ok',
					'msg' => "预授权转支付扣款通知处理成功",
				]);exit;

			}else{
				// 提交事务
				DB::rollback();
				echo json_encode([
					'status' => 'error',
					'msg' => "异步回调处理错误",
				]);exit;
			}


		} catch (\Exception $e) {
			DB::rollBack();
			LogApi::error(__method__.'[fundauthToPayNotify回调预授权转支付]回调接口异常 ', [$e,$params]);
			$this->innerErrMsg(__METHOD__ . "()预授权转支付回调接口异常 " .$e->getMessage());

		}

	}

   /**
     * 订单清算退款回调地址
     * Author: heaven
     * @param Request $request
     */
    public function refundClean(Request $request){

        DB::beginTransaction();
        try{
            $input = file_get_contents("php://input");
            LogApi::info(__method__.'[cleanAccount回调退款]回调接收',$input);
            $param = json_decode($input,true);
            $rule = [
                'out_refund_no'=>'required', //订单系统退款码
                'refund_no'=>'required', //支付系统退款码
                'status'=>'required',//状态类型
                'reason'=>'required', //错误原因
            ];

            $validateParams = $this->validateParams($rule,$param);
			if ($validateParams['code']!=0) {
                LogApi::error(__method__.'[cleanAccount回调退款]参数校验错误',$validateParams);
				$this->innerErrMsg($validateParams['msg']);
			}
            if ($param['status']!='success'){
                LogApi::error(__method__.'[cleanAccount回调退款]状态错误',$param);
            }

            //更新查看清算表的状态
            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['refund_clean_no'=>$param['out_refund_no']]);
            if ($orderCleanInfo['code']) {
				DB::rollback();
                LogApi::error(__method__.'[cleanAccount回调退款][清算记录]不存在');
                $this->innerErrMsg(__METHOD__."() ".microtime(true).' 订单清算记录不存在');
				exit;
            }
            $orderCleanInfo = $orderCleanInfo['data'];
			
			// 操作员信息
			$userinfo = [
				'uid'		=> $orderCleanInfo['operator_uid'],
				'username'	=> $orderCleanInfo['operator_username'],
				'type'		=> $orderCleanInfo['operator_type'],
			];
			
            //查看清算状态是否已退款
            if ( $orderCleanInfo['refund_status'] == OrderCleaningStatus::refundUnpayed ){// 待退款状态

                //更新订单清算退款状态
                $orderParam = [
                    'clean_no' => $orderCleanInfo['clean_no'],
                    'out_refund_no'     => $param['refund_no'],
                    'refund_status' => OrderCleaningStatus::refundPayd, // 已退款
                ];
                $success = OrderCleaning::upOrderCleanStatus($orderParam);
				
                if (!$success) {
                    //查看其他状态是否完成，如果完成，更新整体清算的状态
                    if ($orderCleanInfo['auth_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                        $orderCleanInfo['auth_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                        $orderCleanParam = [
                            'clean_no' => $orderCleanInfo['clean_no'],
                            'status' => OrderCleaningStatus::orderCleaningComplete
                        ];
                        $success = OrderCleaning::upOrderCleanStatus($orderCleanParam);

                        if (!$success) {	// 成功
                            //更新业务系统的状态
                            $businessParam = [
                                'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                                'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                                'status'		=> $param['status'],	// 支付状态  processing：处理中；success：支付完成
                            ];
                            $b =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'],
									$businessParam['business_no'],
									$businessParam['status'],
									$userinfo);
							if( !$b ){
                                DB::rollBack();
                                OrderCleaning::warningCleanOrder('回调退款业务接口异常:',[$businessParam, $userinfo,$b]);
                                LogApi::error(__method__.'[cleanAccount回调退款]业务接口失败OrderCleaning::getBusinessCleanCallback', [$businessParam, $userinfo,$b]);
                                $this->innerErrMsg(__METHOD__."() ".microtime(true).' 退款回调业务接口失败');
							}

                            LogApi::info(__method__.'[cleanAccount回调退款]业务接口OrderCleaning::getBusinessCleanCallback返回的结果', $success);
							
                        }  else {
							DB::rollBack();
                            OrderCleaning::warningCleanOrder('回调退款更新清算status异常:',$orderCleanParam);
                            LogApi::error(__method__.'[cleanAccount回调退款]业务回调更新整体清算的状态失败', $orderCleanParam);
                            $this->innerErrMsg(__METHOD__."() ".microtime(true).' 退款业务回调更新整体清算的状态失败');
                        }
                    }
                } else {
					DB::rollBack();
                    $this->innerErrMsg();
                    OrderCleaning::warningCleanOrder('回调退款状态更新异常:',$orderParam);
                    LogApi::error(__method__.'[cleanAccount回调退款]退款业务状态更新失败');
                    $this->innerErrMsg(__METHOD__."() ".microtime(true).' 退款业务状态更新失败');
                }

            } else { // 非待退款状态
				DB::rollBack();
                LogApi::error(__method__.'[cleanAccount回调退款]订单清算退款状态无效');
                $this->innerOkMsg(__METHOD__ . "() " . microtime(true) . " {$param['out_refund_no']}订单清算退款状态无效");
            }
			DB::commit();
            $this->innerOkMsg();


        } catch (\Exception $e)  {
			DB::rollBack();
            LogApi::error(__method__.'[cleanAccount回调退款]订单清算退款回调地址异常',  [$e,$param]);
            $this->innerErrMsg(__METHOD__ . "()订单清算退款回调地址异常 " .$e->getMessage());

        }


        }


    /**
     * 订单清算退押金回调接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unFreezeClean(Request $request)
    {
        DB::beginTransaction();
        try{
            $input = file_get_contents("php://input");
            LogApi::info(__method__.'[cleanAccount回调解除预授权]订单清算退押金回调接口回调参数:',$input);
            $param = json_decode($input,true);
            $rule = [
                "status"=>'required',                //类型：String  必有字段  备注：init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
                "trade_no"=>'required',                //类型：String  必有字段  备注：支付平台交易码
                "out_trade_no"=>'required',                //类型：String  必有字段  备注：业务系统交易码
                "fundauth_no"=>'required',                //类型：String  必有字段  备注：支付平台授权
            ];

            $validateParams = $this->validateParams($rule,$param);
            if ($validateParams['code']!=0) $this->innerErrMsg($validateParams['msg']);
            //更新查看清算表的状态

            // 开启事务

            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['auth_unfreeze_no'=>$param['out_trade_no']]);

//            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['clean_no'=>'CA70407132618675']);

            if (!isset($orderCleanInfo['code']) || $orderCleanInfo['code']) {
                LogApi::error(__method__.'[cleanAccount回调解除预授权]订单清算记录不存在');
                $this->innerErrMsg('订单清算记录不存在');
				exit;
            }
            $orderCleanInfo = $orderCleanInfo['data'];

            // 操作员信息
            $userinfo = [
                'uid'		=> $orderCleanInfo['operator_uid'],
                'username'	=> $orderCleanInfo['operator_username'],
                'type'		=> $orderCleanInfo['operator_type'],
            ];
            //查看退押金状态是否是待退押金状态
            if ($orderCleanInfo['auth_unfreeze_status']==OrderCleaningStatus::depositUnfreezeStatusUnpayed){

                //更新订单退押金状态
                $orderParam = [
                    'clean_no' => $orderCleanInfo['clean_no'],
                    'out_unfreeze_trade_no'     => $param['trade_no'],
                    'auth_unfreeze_status' => OrderCleaningStatus::depositUnfreezeStatusPayd
                ];
                $success = OrderCleaning::upOrderCleanStatus($orderParam);
                if (!$success) {

                    //查看其他状态是否完成，如果完成，更新整体清算的状态
                    if ($orderCleanInfo['auth_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                        $orderCleanInfo['refund_status']!=OrderCleaningStatus::refundUnpayed){
                        $orderCleanParam = [
                            'clean_no' => $orderCleanInfo['clean_no'],
                            'status' => OrderCleaningStatus::orderCleaningComplete
                        ];
                        $success = OrderCleaning::upOrderCleanStatus($orderCleanParam);
                        if (!$success) {
                            //更新业务系统的状态
                            $businessParam = [
                                'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                                'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                                'status'		=> $param['status'],	// 支付状态  processing：处理中；success：支付完成
                            ];
                            $success =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'], $businessParam['business_no'],
                                $businessParam['status'],$userinfo);
                            if( !$success ){

                                DB::rollBack();
                                OrderCleaning::warningCleanOrder('清算退押金回调业务异常:',[$businessParam, $userinfo,$success]);
                                LogApi::error(__method__.'[cleanAccount回调解除预授权]回调业务接口失败OrderCleaning::getBusinessCleanCallback', [$businessParam, $userinfo,$success]);
                                $this->innerErrMsg('回调业务接口失败');
                            }

                            LogApi::debug(__method__.'[cleanAccount回调解除预授权]回调业务接口参数及结果OrderCleaning::getBusinessCleanCallback', [$businessParam, $success]);
                        }  else {

                            DB::rollBack();
                            OrderCleaning::warningCleanOrder('回调解除预授权更新订单status状态异常:',$orderCleanParam);
                            LogApi::error(__method__.'[cleanAccount回调解除预授权]业务回调更新整体清算的状态失败', $orderCleanParam);
                            $this->innerErrMsg('押金解押业务回调更新整体清算的状态失败');
                        }
                    }
                } else {
                    DB::rollBack();
                    OrderCleaning::warningCleanOrder('回调解除预授权更新订单退押金状态异常:',$orderParam);
                    LogApi::error(__method__.'[cleanAccount回调解除预授权] 更新订单退押金状态失败');
                    $this->innerErrMsg('更新订单退押金状态失败');

                }

            } else {

               // DB::rollBack();
                LogApi::error(__method__.'[cleanAccount回调解除预授权]订单清算退款状态无效',$param);
                $this->innerErrMsg('订单清算解押状态无效');
            }
            DB::commit();

            //发起退款的数据
            OrderCleaning::refundRequest($orderCleanInfo);
            $this->innerOkMsg();


        } catch (\Exception $e) {

            DB::rollBack();
            LogApi::error(__method__.'[cleanAccount回调解除预授权]订单清算退押金回调接口异常 ' ,$e);
            $this->innerErrMsg(__METHOD__ . "()订单清算退押金回调接口异常 ");

        }


    }


    /**
     * 订单清算押金转支付回调接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfreezeAndPayClean(Request $request)
    {

        // 开启事务
        DB::beginTransaction();
        try{
            $input = file_get_contents("php://input");
            LogApi::info(__method__.'[cleanAccount回调预授权转支付]订单清算退押金回调接口回调参数:'.$input);
            $param = json_decode($input,true);
            $rule = [
                "status"=>'required',                //类型：String  必有字段  备注：init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
                "trade_no"=>'required',                //类型：String  必有字段  备注：支付平台交易码
                "out_trade_no"=>'required',                //类型：String  必有字段  备注：业务系统交易码
                "fundauth_no"=>'required',                //类型：String  必有字段  备注：支付平台授权
            ];

            $validateParams = $this->validateParams($rule,$param);
            if ($validateParams['code']!=0) $this->innerErrMsg($validateParams['msg']);
            if ($param['status']!='success'){
                LogApi::error(__METHOD__.'() '.microtime(true).'返回结果:'.$input.'订单清算退款失败');
            }

            //更新查看清算表的状态
            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['auth_deduction_no'=>$param['out_trade_no']]);
            if ($orderCleanInfo['code']) {
                LogApi::error(__method__.'[cleanAccount回调预授权转支付]订单清算记录不存在');
                $this->innerErrMsg('订单清算记录不存在');

            }
            $orderCleanInfo = $orderCleanInfo['data'];
            // 操作员信息
            $userinfo = [
                'uid'		=> $orderCleanInfo['operator_uid'],
                'username'	=> $orderCleanInfo['operator_username'],
                'type'		=> $orderCleanInfo['operator_type'],
            ];
            //查看清算状态是否已支付
            if ($orderCleanInfo['auth_deduction_status']==OrderCleaningStatus::depositDeductionStatusUnpayed){

                //更新订单清算退款状态
                $orderParam = [
                    'clean_no' => $orderCleanInfo['clean_no'],
                    'out_unfreeze_pay_trade_no'     => $param['trade_no'],
                    'auth_deduction_status' => OrderCleaningStatus::depositDeductionStatusPayd
                ];
                $success = OrderCleaning::upOrderCleanStatus($orderParam);
                if (!$success) {
                    //查看其他状态是否完成，如果完成，更新整体清算的状态
                    if ($orderCleanInfo['refund_status']!=OrderCleaningStatus::refundUnpayed &&
                        $orderCleanInfo['auth_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                        $orderCleanParam = [
                            'clean_no' => $orderCleanInfo['clean_no'],
                            'status' => OrderCleaningStatus::orderCleaningComplete
                        ];
                        $success = OrderCleaning::upOrderCleanStatus($orderCleanParam);

                        if (!$success) {
                            //更新业务系统的状态
                            $businessParam = [
                                'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                                'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                                'status'		=> $param['status'],	// 支付状态  processing：处理中；success：支付完成
                            ];
                            $success =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'], $businessParam['business_no'],
                                $businessParam['status'],$userinfo);
                            if( !$success ){//
                                DB::rollBack();
                                OrderCleaning::warningCleanOrder('清算押金转支付回调业务异常:',[$businessParam,$userinfo,$success]);
                                LogApi::error(__method__.'[cleanAccount回调预授权转支付]回调业务失败参数及结果OrderCleaning::getBusinessCleanCallback', [$businessParam,$userinfo,$success]);
                                $this->innerErrMsg('押金转支付回调业务业务失败');
                            }
                            LogApi::info(__method__.'[cleanAccount回调预授权转支付]调业务接口参数及结果OrderCleaning::getBusinessCleanCallback', [$businessParam,$success]);
                        }  else {
                            DB::rollBack();
                            OrderCleaning::warningCleanOrder('回调预授权转支付更新status的状态异常:',$orderCleanParam);
                            LogApi::error(__method__.'[cleanAccount回调预授权转支付]回调更新整体清算的状态失败', $orderCleanParam);
                            $this->innerErrMsg('押金转支付回调更新整体清算的状态失败');
                        }
                    }
                } else {
                    DB::rollBack();
                    OrderCleaning::warningCleanOrder('回调预授权转支付更新押金转支付的状态异常:',$orderParam);
                    LogApi::error(__method__.'[cleanAccount回调预授权转支付]更新押金转支付的状态失败');
                    $this->innerErrMsg('押金转支付的状态更新失败');
                }

            } else {

                LogApi::error(__method__.'[cleanAccount回调预授权转支付]订单清算退款状态无效');
                $this->innerErrMsg('订单清算押金转支付状态无效');
            }
            DB::commit();
            //发起解押金的请求
            OrderCleaning::unfreezeRequest($orderCleanInfo);
            //发起退款的数据
            OrderCleaning::refundRequest($orderCleanInfo);
            $this->innerOkMsg();


        } catch (\Exception $e) {
            DB::rollBack();
            LogApi::error(__method__.'[cleanAccount回调预授权转支付]回调接口异常 ', [$e,$param]);
            $this->innerErrMsg(__METHOD__ . "()订单清算押金转支付回调接口异常 " .$e->getMessage());

        }

    }



    /**
     * 订单清算微回收回调接口
     * Author: heaven
     * @param Request $request
     * $param = [
     *
     * {
        "payment_no":"10A92747476192665",
        "out_payment_no":"10A92747476192665",
        "amount":1,
        "status":"success"
        }
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function lebaiUnfreezeClean()
    {

        DB::beginTransaction();
        try{

            $input = file_get_contents("php://input");
            LogApi::info(__method__.'[lebaiCleanAccount微回收回调清算退押金回调接口回调参数:', $input);
            $param = json_decode($input,true);
            $rule = [
                "out_payment_no"=>'required',           //类型：String  必有字段  备注：业务系统交易码
                "amount"=>'required',                //类型：String  必有字段  备注：扣押金金额
                "status"=>'required',               // //类型：String  必有字段  备注：init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
            ];

            $validateParams = $this->validateParams($rule,$param);
            if ($validateParams['code']!=0) $this->innerErrMsg($validateParams['msg']);

            if ($param['status']!='success'){
                LogApi::error(__METHOD__.'() '.microtime(true).'[cleanAccount微回收回调清算退押金回调返回结果:'.$input.'订单清算退款失败');
            }

            //更新查看清算表的状态
            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['payment_no'=>$param['out_payment_no']]);
            if ($orderCleanInfo['code']) {
                LogApi::error(__method__.'[lebaiCleanAccount微回收回调预授权转支付]订单清算记录不存在');
                $this->innerErrMsg('订单清算记录不存在');

            }

            $orderCleanInfo = $orderCleanInfo['data'];

            // 操作员信息
            $userinfo = [
                'uid'		=> $orderCleanInfo['operator_uid'],
                'username'	=> $orderCleanInfo['operator_username'],
                'type'		=> $orderCleanInfo['operator_type'],
            ];

            //查看清算状态是否已解除
            if (($orderCleanInfo['auth_unfreeze_status']== OrderCleaningStatus::depositUnfreezeStatusUnpayed) || ($orderCleanInfo['auth_deduction_status']== OrderCleaningStatus::depositDeductionStatusUnpayed)){

                //更新订单清算押金转支付状态
                $orderParam = [
                    'payment_no' => $param['out_payment_no'],
                ];


                $success = OrderClearingRepository::upLebaiOrderCleanStatus($orderParam);

                if ($success) {
                    //更新业务系统的状态
                    $businessParam = [
                        'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                        'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                        'status'		=> 'success',	// 支付状态  processing：处理中；success：支付完成
                    ];

                    LogApi::info(__method__.'[lebaiCleanAccount微回收回调订单清算回调业务接口回调参数:', [$businessParam,$userinfo]);
                    $success =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'], $businessParam['business_no'], $businessParam['status'], $userinfo);
                    LogApi::info(__method__.'[lebaiCleanAccount微回收回调订单清算回调结果OrderCleaning::getBusinessCleanCallback业务接口回调参数:', [$businessParam,$userinfo,$success]);
                    if (!$success) {
                        DB::rollBack();
                        OrderCleaning::warningCleanOrder('微回收回调订单清算业务接口失败:',[$businessParam,$userinfo,$success]);
                        $this->innerErrMsg('微回收回调订单清算回调业务结果失败');
                    }
                    DB::commit();
                    $this->innerOkMsg();
                } else {
                    DB::rollBack();
                    OrderCleaning::warningCleanOrder('微回收回调更新清算状态失败:',$orderParam);
                    LogApi::error(__method__.'[lebaiCleanAccount微回收回调 更新订单清算状态失败');
                    $this->innerErrMsg('微回收回调更新订单清算状态失败');
                }
            } else {


                LogApi::info(__method__.'[lebaiCleanAccount微回收回调订单清算退款状态无效');
            }
            $this->innerOkMsg();

        } catch (\Exception $e) {
            DB::rollBack();
            LogApi::error(__method__.'[lebaiCleanAccount微回收回调订单清算押金转支付回调接口异常 ',$e);
            $this->innerErrMsg('微回收回调订单清算押金转支付回调接口异常');

        }

    }

	/**
	 * 分期扣款异步回调处理
	 * @requwet Array
	 * [
	 *      'reason'            => '', 【必须】 String 错误原因
	 *      'status'            => '', 【必须】 int：success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
	 *      'agreement_no'      => '', 【必须】 String 支付平台签约协议号
	 *      'out_agreement_no'  => '', 【必须】 String 业务系统签约协议号
	 *      'trade_no'          => '', 【必须】 String 支付平台交易码
	 *      'out_trade_no'      => '', 【必须】 String 业务平台交易码
	 * ]
	 * @return String FAIL：失败  SUCCESS：成功
	 */
	public function withholdCreatePayNotify(){

		$input = file_get_contents("php://input");
		LogApi::info('[crontabCreatepay]进入分期扣款回调逻辑start', $input);
//		LogApi::info('代扣异步通知', $input);

		$params = json_decode($input,true);
//        //数据签名验证
//        $sign = $params['sign'];
//        unset($params['sign']);
//        ksort($params);
//        $b = \App\Lib\AlipaySdk\sdk\aop\AopClient::verifySign(http_build_query($params),$sign);
//        if(!$b){
//            echo json_encode([
//                'status' => 'error',
//                'msg' => 'Signature error ',
//            ]);exit;
//        }
		if( is_null($params) ){
			LogApi::info('[crontabCreatepay]进入分期扣款回调逻辑参数为空', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			LogApi::info('[crontabCreatepay]进入分期扣款回调逻辑参数不是数组', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is array',
			]);exit;
		}



		try {

			// 校验扣款交易状态
			$status_info = \App\Lib\Payment\CommonWithholdingApi::deductQuery($params);
			if( $status_info['status'] != 'success' ){//
				LogApi::error('[crontabCreatepay]进入分期扣款回调逻辑：校验扣款交易状态', $status_info);
				echo json_encode([
					'status' => 'error',
					'msg' => 'status not success',
				]);exit;
			}

			// 开启事务
			DB::beginTransaction();

			$b = \App\Order\Modules\Repository\Order\Instalment::paySuccess($params);

			LogApi::info('[crontabCreatepay]进入分期扣款回调逻辑：分期更新支付状态和支付时间，返回的结果', $b);
			if( $b ){
				// 提交事务
				DB::commit();
				echo json_encode([
					'status' => 'ok',
					'msg' => "代扣扣款通知处理成功",
				]);exit;

			}else{
				// 提交事务
				DB::rollback();
				echo json_encode([
					'status' => 'error',
					'msg' => "异步回调处理错误",
				]);exit;
			}


		} catch (\App\Lib\NotFoundException $exc) {
			LogApi::error('[crontabCreatepay]进入分期扣款回调逻辑：代扣扣款异步处理失败', $exc);
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			LogApi::error('[crontabCreatepay]进入分期扣款回调逻辑：代扣扣款异步处理失败', $exc);
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
		}


	}



	 /**
	 * 收支明细表
	 * @requwet Array
	 * [
	 * 		'appid'				=> '', // 入账渠道：1生活号'
	 * 		'business_type'		=> '', // 订单号
	 *		'channel'			=> '', // 入账方式
	 * 		'amount'			=> '', // 金额
	 * 		'create_time'		=> '', // 创建时间
	 * ]
	 * @return array
	 *
	 */
	public function payIncomeQuery(Request $request){
		$request               = $request->all()['params'];
		$additional['page']    = isset($request['page']) ? $request['page'] : 1;
		$additional['limit']   = isset($request['limit']) ? $request['limit'] : config("web.pre_page_size");

		$params         = filter_array($request, [
			'appid'            	=> 'required',
			'business_type'     => 'required',
			'channel'      		=> 'required',
			'amount'  			=> 'required',
			'begin_time'       	=> 'required',
			'end_time'       	=> 'required',
		]);

		$list = \App\Order\Modules\Repository\OrderPayIncomeRepository::queryList($params,$additional);
		
		if(!is_array($list)){
			return apiResponse([], ApiStatus::CODE_50000, "程序异常");
		}
		return apiResponse($list,ApiStatus::CODE_0,"success");
	}

    /**
     * 收支明细表导出
     * @requwet Array
     * [
     * 		'appid'				=> '', // 入账渠道：1生活号'
     * 		'business_type'		=> '', // 订单号
     *		'channel'			=> '', // 入账方式
     * 		'amount'			=> '', // 金额
     * 		'create_time'		=> '', // 创建时间
     * ]
     * @return array
     *
     */
    public function payIncomeQueryExport(Request $request){
        set_time_limit(0);
        $params = $request->all();
        $pageSize = 50000;
        if (isset($params['size']) && $params['size']>=50000) {
            $pageSize = 50000;
        } else {
            $pageSize = $params['size'];
        }
        $params['page'] = $params['page']?? 1;
        $outPages       = $params['page']?? 1;

        $total_export_count = $pageSize;
        $pre_count = $params['smallsize']?? 500;

        $smallPage = ceil($total_export_count/$pre_count);
        $abc = 1;

        $headers = ['名称', '姓名','电话','入账发起时间','入账类型', '入账方式','业务编号','入账金额','拿趣用订单编号','业务平台交易码','支付平台交易码','缴款用途'];

        $orderExcel = array();
        while(true) {
            if ($abc>$smallPage) {
                break;
            }
            $offset = ($outPages - 1) * $total_export_count;
            $params['page'] = intval(($offset / $pre_count)+ $abc) ;
            ++$abc;
            $orderData = array();
            LogApi::debug("[payIncomeQueryExport]导出参数",['params'=>$params,'pre_count'=>$pre_count]);

            $orderData = \App\Order\Modules\Repository\OrderPayIncomeRepository::queryListExport($params,$pre_count);
            $orderDataArray=objectToArray($orderData);
            LogApi::debug("[payIncomeQueryExport]查询结果",$orderData);
            if ($orderDataArray) {
                $data = array();
                foreach ($orderDataArray as $item) {
					// 缴款用途
					$business_type_name = \App\Order\Modules\Repository\Pay\UnderPay\UnderPayStatus::getBusinessTypeName($item['business_type']);
					if(!$business_type_name){
						$businessType = \App\Order\Modules\Inc\OrderStatus::getBusinessName($item['business_type']);
						$business_type_name = "业务类型-" . $businessType . "支付";
					}

                    $data[] = [
                        $item['name'],
						$item['realname'],
						$item['mobile'],
                        date('Y-m-d H:i:s', $item['create_time']),
                        OrderStatus::getBusinessName( $item['business_type']),
                        Channel::getBusinessName($item['channel']),
                        $item['business_no'],
                        $item['amount'],
                        $item['order_no'],
                        $item['trade_no'],
                        $item['out_trade_no'],
						$business_type_name,
                    ];

                }
                LogApi::debug("【payIncomeQueryExport】导出数据列表",$data);
                $orderExcel =  \App\Lib\Excel::csvWrite1($data,  $headers, '入账列表导出',$abc);

            }else{
                break;
            }
        }

        return $orderExcel;
        exit;
    }



	/**
	 * 支付单状态查询
	 * @requwet Array
	 * [
	 * 		'out_payment_no' => 1,	// 支付编号
	 * ]
	 * @return array
	 */
	public function paymentStatus(Request $request){
		$params     = $request->all();
		// 参数过滤
		$rules 		= [
			'out_payment_no'     => 'required',
		];

		$validateParams = $this->validateParams($rules,$params);
		if ($validateParams['code'] != 0) {
			return apiResponse([],$validateParams['code']);
		}

		$params = $params['params'];

		try{

			$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByPaymentNo($params['out_payment_no']);
			$paymentStatus = $payObj->getPaymentStatus();

		} catch (\App\Lib\NotFoundException $exc) {
			LogApi::error('支付通知处理失败',$exc);
			return apiResponse([],ApiStatus::CODE_20001, "参数错误");
		}

		return apiResponse(['status' => $paymentStatus],ApiStatus::CODE_0,"success");

	}
    /**
     * 预定金退款回调地址
     * Author: qinliping
     * @param Request $request
     */
    public function appointmentRefund(Request $request)
    {
        LogApi::debug("回调接收");
        DB::beginTransaction();
        try {
            $input = file_get_contents("php://input");
            LogApi::info(__method__ . '[appointmentRefund回调退款]回调接收', $input);
            $param = json_decode($input, true);
            $rule = [
                'out_refund_no' => 'required', //订单系统退款码
                'refund_no'      => 'required', //支付系统退款码
                'status'         => 'required', //状态类型
                'reason'         => 'required', //错误原因
            ];

            $validateParams = $this->validateParams($rule, $param);
            if ($validateParams['code'] != 0) {
                LogApi::error(__method__ . '[appointmentRefund回调退款]参数校验错误', $validateParams);
                $this->innerErrMsg($validateParams['msg']);
            }
            if ($param['status'] != 'success') {
                LogApi::error(__method__ . '[appointmentRefund回调退款]状态错误', $param);
            }
            //查看预定单的支付状态
            $activityDestineInfo=\App\Activity\Modules\Repository\Activity\ActivityDestine::getByNo($param['out_refund_no']);
            if(!$activityDestineInfo){
                DB::rollback();
                LogApi::error(__method__.'[appointmentRefund回调退款][预定记录]不存在');
                $this->innerErrMsg(__METHOD__."() ".microtime(true).' 预定记录不存在');
                exit;
            }
            $destineInfo=$activityDestineInfo->getData();

            //查看预定是否已退款
            if ( $destineInfo['destine_status'] == DestineStatus::DestinePayed){// 已支付，已下单

                //更新业务系统的状态
                $businessParam = [
                    'business_type' => OrderStatus::BUSINESS_DESTINE,	// 业务类型
                    'business_no'	=> $param['destine_status'],	// 业务编码
                    'status'		=> $param['status'],	// 支付状态  processing：处理中；success：支付完成
                ];
                //操作用户
                $userinfo=[
                    'uid'      => 1,       //操作员id
                    'username' =>'admin',//操作员名称
                    'type'      =>'1'      //操作类型
                ];
                $b =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'],
                    $businessParam['business_no'],
                    $businessParam['status'],$userinfo);
                if( !$b ){
                    DB::rollBack();
                    LogApi::error(__method__.'[appointmentRefund回调退款]业务接口失败OrderCleaning::getBusinessCleanCallback', [$businessParam,$userinfo,$b]);
                    $this->innerErrMsg(__METHOD__."() ".microtime(true).' 退款回调业务接口失败');
                }

            } else { // 非待退款状态
                DB::rollBack();
                LogApi::error(__method__.'[appointmentRefund回调退款]预定状态无效');
                $this->innerErrMsg(__METHOD__ . "() " . microtime(true) . " {$param['out_refund_no']}预定状态无效");
            }
            DB::commit();
            $this->innerOkMsg();

        }catch (\Exception $exc){

        }
    }

	/**
	 * 扣除押金支付通知
	 * Author: heaven
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deduDepositNotify(){
		$input = file_get_contents("php://input");
		LogApi::info('[deduDepositNotify]扣除押金回调接口回调参数:'.$input);

		$params = json_decode($input,true);

		if( is_null($params) ){
			LogApi::info('[deduDepositNotify]扣除押金回调逻辑参数为空', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			LogApi::info('[deduDepositNotify]扣除押金回调逻辑参数不是数组', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is array',
			]);exit;
		}

		try{
			// 开启事务
			DB::beginTransaction();

			$b = \App\Order\Modules\Service\OrderOverdueDeduction::OverdueDeductionNotify($params);
			if( $b ){
				// 提交事务
				DB::commit();
				echo json_encode([
					'status' => 'ok',
					'msg' => "扣除押金支付扣款通知处理成功",
				]);exit;

			}else{
				// 提交事务
				DB::rollback();
				echo json_encode([
					'status' => 'error',
					'msg' => "异步回调处理错误",
				]);exit;
			}

		} catch (\Exception $e) {
			DB::rollBack();
			LogApi::error(__method__.'[deduDepositNotify]扣除押金回调接口异常 ', [$e,$params]);
			$this->innerErrMsg(__METHOD__ . "()扣除押金回调接口异常 " .$e->getMessage());

		}

	}


	/**
	 * 还机扣除押金支付通知
	 * Author: heaven
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function givebackDeductionDepositNotify(){
		$input = file_get_contents("php://input");
		LogApi::info('[givebackDeductionDepositNotify]还机扣除押金回调接口回调参数:'.$input);

		$params = json_decode($input,true);

		if( is_null($params) ){
			LogApi::info('[givebackDeductionDepositNotify]还机扣除押金回调逻辑参数为空', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			LogApi::info('[givebackDeductionDepositNotify]还机扣除押金回调逻辑参数不是数组', $params);
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is array',
			]);exit;
		}

		try{
			// 开启事务
			DB::beginTransaction();

			$b = \App\Order\Modules\Service\OrderGiveback::deductionDepositNotify($params);
			if( $b ){
				// 提交事务
				DB::commit();
				echo json_encode([
					'status' => 'ok',
					'msg' => "还机扣除押金支付扣款通知处理成功",
				]);exit;

			}else{
				// 提交事务
				DB::rollback();
				echo json_encode([
					'status' => 'error',
					'msg' => "异步回调处理错误",
				]);exit;
			}

		} catch (\Exception $e) {
			DB::rollBack();
			LogApi::error(__method__.'[givebackDeductionDepositNotify]还机扣除押金回调接口异常 ', [$e,$params]);
			$this->innerErrMsg(__METHOD__ . "()还机扣除押金回调接口异常 " .$e->getMessage());

		}

	}


}
