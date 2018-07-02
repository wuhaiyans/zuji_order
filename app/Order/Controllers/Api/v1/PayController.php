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
        $rules = [
            'callback_url'  => 'required',
            'order_no'  => 'required',
            'name'  => 'required',
            'pay_channel_id'  => 'required',
            'user_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if (empty($validateParams) || $validateParams['code']!=0) {
            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
		
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
			$paymentUrl = $pay->getCurrentUrl($params['pay_channel_id'], [
					'name'=> $params['name'],
					'front_url' => $params['callback_url'],
			]);
			return apiResponse(['url'=>$paymentUrl['url']],ApiStatus::CODE_0);
		} catch (\Exception $exs) {
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
		LogApi::info('支付异步通知', $input);
		
		$params = json_decode($input,true);
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
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByPaymentNo( $params['out_payment_no'] );
			
			if( $pay->isSuccess() ){// 已经支付成功
				DB::rollBack();
				echo json_encode([
					'status' => 'ok',
					'msg' => 'payment notice repeated',
				]);exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needPayment() ){
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
			
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			DB::rollBack();
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
		} catch (\Exception $exc) {
			DB::rollBack();
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
		}
		
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
				'withhold_channel' => $params['channel'],
			]);
						
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			LogApi::error('代扣签约异步通知处理失败', $exc);
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			LogApi::error('代扣签约异步通知处理失败', $exc);
			echo $exc->getMessage();
		}
		
		DB::rollBack();
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
	public function withholdUnsignNotify(Request $request)
	{
		$params     = $request->all();

		$rules = [
			'reason'            => 'required',
			'status'            => 'required',
			'agreement_no'      => 'required',
			'out_agreement_no'  => 'required',
			'user_id'           => 'required',
		];
		// 参数过滤
		$validateParams = $this->validateParams($rules,$params);
		if ($validateParams['code'] != 0) {
			return apiResponse([],$validateParams['code']);
		}

		// 解约成功 修改协议表
		$params = $params['params'];

		if($params['status'] == "success"){
			try{
				// 查询用户协议
				$withhold = \App\Order\Modules\Repository\Pay\WithholdQuery::getByWithholdNo( $params['out_agreement_no'] );
				$withhold->unsignSuccess();
			} catch(\Exception $exc){

				echo "FAIL";exit;
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
			// 状态
			if( $status_info['status'] != 'success' ){// 未授权
				echo 'fundauth status not success';exit;
			}
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo 'fundauth notice repeated ';exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needFundauth() ){
				echo 'fundauth not need ';exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 代扣签约处理
			$pay->fundauthSuccess([
				'out_fundauth_no' => $params['fundauth_no'],	// 支付系统资金预授权编码
				'total_amount' => sprintf('%0.2f',$params['total_freeze_amount']/100),
				'fundauth_channel' => $params['channel'],
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
	 * 
	 */
	public function fundautUnfreezeNotify()
	{
		
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

   /**
     * 订单清算退款回调地址
     * Author: heaven
     * @param Request $request
     */
    public function refundClean(Request $request){

        try{
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
            if ($validateParams['code']!=0) $this->innerErrMsg($validateParams['msg']);
            if ($param['status']!='success'){
                LogApi::info(__METHOD__.'() '.microtime(true).'返回结果:'.$input.'订单清算退款失败');
            }
            //更新查看清算表的状态
            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['clean_no'=>$param['out_refund_no']]);
            if ($orderCleanInfo['code']) {
                LogApi::info(__METHOD__."() ".microtime(true)." 订单清算记录不存在");
                $this->innerErrMsg(__METHOD__."() ".microtime(true).' 订单清算记录不存在');

            }
            $orderCleanInfo = $orderCleanInfo['data'];
            //查看清算状态是否已支付
            if ($orderCleanInfo['refund_status']==OrderCleaningStatus::refundUnpayed){

                //更新订单清算退款状态
                $orderParam = [
                    'clean_no' => $param['out_refund_no'],
                    'out_refund_no'     => $param['refund_no'],
                    'refund_status' => OrderCleaningStatus::refundPayd
                ];
                $success = OrderCleaning::upOrderCleanStatus($orderParam);
                if (!$success) {
                    //查看其他状态是否完成，如果完成，更新整体清算的状态
                    if ($orderCleanInfo['auth_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                        $orderCleanInfo['auth_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                        $orderParam = [
                            'clean_no' => $param['out_refund_no'],
                            'status' => OrderCleaningStatus::orderCleaningComplete
                        ];
                        $success = OrderCleaning::upOrderCleanStatus($orderParam);

                        if (!$success) {
                            //更新业务系统的状态
                            $businessParam = [
                                'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                                'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                                'status'		=> $param['status'],	// 支付状态  processing：处理中；success：支付完成
                            ];
                            $success =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'], $businessParam['business_no'], $businessParam['status']);

                            LogApi::info('退款回调业务接口OrderCleaning::getBusinessCleanCallback', $businessParam);
                        }  else {

                            LogApi::info('退款业务回调更新整体清算的状态失败', $orderParam);
                            $this->innerErrMsg(__METHOD__."() ".microtime(true).' 退款业务回调更新整体清算的状态失败');
                        }
                    }
                } else {
                    $this->innerErrMsg();
                    LogApi::info(__METHOD__."() ".microtime(true)." 退款业务状态更新失败");
                    $this->innerErrMsg(__METHOD__."() ".microtime(true).' 退款业务状态更新失败');
                }

            } else {

                LogApi::info(__METHOD__ . "() " . microtime(true) . " {$param['out_refund_no']}订单清算退款状态无效");
                $this->innerErrMsg(__METHOD__ . "() " . microtime(true) . " {$param['out_refund_no']}订单清算退款状态无效");
            }
            $this->innerOkMsg();


        } catch (\Exception $e)  {

            LogApi::info(__METHOD__ . "()订单清算退款回调地址异常 " .$e->getMessage(),  $param);
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

        try{

            $input = file_get_contents("php://input");
            LogApi::info(__METHOD__.'() '.microtime(true).'订单清算退押金回调接口回调参数:'.$input);
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

            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['clean_no'=>$param['out_trade_no']]);

            if (!isset($orderCleanInfo['code']) || $orderCleanInfo['code']) {
                LogApi::info(__METHOD__."() ".microtime(true)." 订单清算记录不存在");
                $this->innerErrMsg('订单清算记录不存在');

            }
            $orderCleanInfo = $orderCleanInfo['data'];
            //查看退押金状态是否是待退押金状态
            if ($orderCleanInfo['auth_unfreeze_status']==OrderCleaningStatus::depositUnfreezeStatusUnpayed){

                //更新订单退押金状态
                $orderParam = [
                    'clean_no' => $param['out_trade_no'],
                    'out_unfreeze_trade_no'     => $param['trade_no'],
                    'auth_unfreeze_status' => OrderCleaningStatus::depositUnfreezeStatusPayd
                ];
                $success = OrderCleaning::upOrderCleanStatus($orderParam);
                if (!$success) {
                    //查看其他状态是否完成，如果完成，更新整体清算的状态
                    if ($orderCleanInfo['auth_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                        $orderCleanInfo['auth_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                        $orderParam = [
                            'clean_no' => $param['out_trade_no'],
                            'status' => OrderCleaningStatus::orderCleaningComplete
                        ];
                        $success = OrderCleaning::upOrderCleanStatus($orderParam);
                        if (!$success) {
                            //更新业务系统的状态
                            $businessParam = [
                                'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                                'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                                'status'		=> $param['status'],	// 支付状态  processing：处理中；success：支付完成
                            ];
                            $success =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'], $businessParam['business_no'], $businessParam['status']);

                            LogApi::info('押金解押回调业务接口OrderCleaning::getBusinessCleanCallback', $businessParam);
                        }  else {

                            LogApi::info('押金解押业务回调更新整体清算的状态失败', $orderParam);
                            $this->innerErrMsg('押金解押业务回调更新整体清算的状态失败');
                        }
                    }
                } else {
                    LogApi::info(__METHOD__."() ".microtime(true)." 更新订单退押金状态失败");
                    $this->innerErrMsg('更新订单退押金状态失败');
                }

            } else {

                LogApi::info(__METHOD__ . "() " . microtime(true) . " {$param}订单清算退款状态无效");
                $this->innerErrMsg('订单清算解押状态无效');
            }
            $this->innerOkMsg();


        } catch (\Exception $e) {


            LogApi::info(__METHOD__ . "()订单清算退押金回调接口异常 " .$e->getMessage(),$param);
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

        try{
            $input = file_get_contents("php://input");
            LogApi::info(__METHOD__.'() '.microtime(true).'订单清算退押金回调接口回调参数:'.$input);
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
                LogApi::info(__METHOD__.'() '.microtime(true).'返回结果:'.$input.'订单清算退款失败');
            }
            //更新查看清算表的状态
            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['clean_no'=>$param['out_trade_no']]);
            if ($orderCleanInfo['code']) {
                LogApi::info(__METHOD__."() ".microtime(true)." 订单清算记录不存在");
                $this->innerErrMsg('订单清算记录不存在');

            }
            $orderCleanInfo = $orderCleanInfo['data'];
            //查看清算状态是否已支付
            if ($orderCleanInfo['auth_deduction_status']==OrderCleaningStatus::depositDeductionStatusUnpayed){

                //更新订单清算退款状态
                $orderParam = [
                    'clean_no' => $param['out_trade_no'],
                    'out_unfreeze_pay_trade_no'     => $param['trade_no'],
                    'auth_deduction_status' => OrderCleaningStatus::depositDeductionStatusPayd
                ];
                $success = OrderCleaning::upOrderCleanStatus($orderParam);
                if (!$success) {
                    //查看其他状态是否完成，如果完成，更新整体清算的状态
                    if ($orderCleanInfo['auth_deduction_status']!=OrderCleaningStatus::depositDeductionStatusUnpayed &&
                        $orderCleanInfo['auth_unfreeze_status']!=OrderCleaningStatus::depositUnfreezeStatusUnpayed){
                        $orderParam = [
                            'clean_no' => $param['out_trade_no'],
                            'status' => OrderCleaningStatus::orderCleaningComplete
                        ];
                        $success = OrderCleaning::upOrderCleanStatus($orderParam);

                        if (!$success) {
                            //更新业务系统的状态
                            $businessParam = [
                                'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                                'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                                'status'		=> $param['status'],	// 支付状态  processing：处理中；success：支付完成
                            ];
                            $success =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'], $businessParam['business_no'], $businessParam['status']);
                            LogApi::info('押金转支付回调业务接口OrderCleaning::getBusinessCleanCallback', $businessParam);
                        }  else {
                            LogApi::info('押金转支付回调更新整体清算的状态失败', $orderParam);
                            $this->innerErrMsg('押金转支付回调更新整体清算的状态失败');
                        }
                    }
                } else {
                    LogApi::info(__METHOD__."() ".microtime(true)."押金转支付的状态更新失败");
                    $this->innerErrMsg('押金转支付的状态更新失败');
                }

            } else {

                LogApi::info(__METHOD__ . "() " . microtime(true) . " {$param['out_refund_no']}订单清算退款状态无效");
                $this->innerErrMsg('订单清算押金转支付状态无效');
            }
            $this->innerOkMsg();


        } catch (\Exception $e) {
            LogApi::info(__METHOD__ . "()订单清算押金转支付回调接口异常 " .$e->getMessage(),$param);
            $this->innerErrMsg(__METHOD__ . "()订单清算押金转支付回调接口异常 " .$e->getMessage());

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
//		LogApi::info('代扣异步通知', $input);

		$params = json_decode($input,true);
		if( is_null($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is null',
			]);exit;
		}
		if( !is_array($params) ){
			echo json_encode([
				'status' => 'error',
				'msg' => 'notice data is array',
			]);exit;
		}



		try {

			// 校验扣款交易状态
			$status_info = \App\Lib\Payment\CommonWithholdingApi::deductQuery($params);
			if( $status_info['status'] != 'success' ){//
				echo json_encode([
					'status' => 'error',
					'msg' => 'status not success',
				]);exit;
			}

			// 开启事务
			DB::beginTransaction();

			$b = \App\Order\Modules\Repository\Order\Instalment::paySuccess($params);

			if( $b ){
				// 提交事务
				DB::commit();
				echo '{"status":"ok"}';exit;

			}else{
				// 提交事务
				DB::rollback();
				echo json_encode([
					'status' => 'error',
					'msg' => "异步回调处理错误",
				]);exit;
			}


		} catch (\App\Lib\NotFoundException $exc) {
			LogApi::error('代扣扣款异步处理失败', $exc );
			echo json_encode([
				'status' => 'error',
				'msg' => $exc->getMessage(),
			]);exit;
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			LogApi::error('代扣扣款异步处理失败', $exc );
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



}
