<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;


class AlipayController extends Controller
{
    protected $orderTrade;

    public function __construct(Service\OrderTrade $orderTrade)
    {
        $this->orderTrade = $orderTrade;
    }
    public function test(){
//        $params['business_type']=1;
//        $params['business_no']='A531153964431177'; //订单支付也就是订单编号
//        $params['status']='success';
//        $b =Service\OrderPayNotify::callback($params);
//        var_dump($b);die;
        $res =$this->alipayInitialize();
        header("Location: ".$res['url']);
    }

    /**
     * 代扣+预授权
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function withholdFundAuth(Request $request){
        $params =$request->all();
        $rules = [
            'callback_url'  => 'required',
            'order_no'  => 'required',
            'fundauth_amount'  => 'required',
            'channel_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if (empty($validateParams) || $validateParams['code']!=0) {
            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
		
		//-+--------------------------------------------------------------------
		// | 查询支付单，查询失败则创建
		//-+--------------------------------------------------------------------
		try{
			//验证是否已经创建过，创建成功，返回true,未创建会抛出异常进行创建
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,$params['order_no'] );
			
		} catch (\App\Lib\NotFoundException $e) {
			$payData = [
				'businessType' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,// 业务类型 
				'businessNo' => $params['order_no'],// 业务编号
				'fundauthAmount' => $params['fundauth_amount'],
			];
			try{
				$pay = \App\Order\Modules\Repository\Pay\PayCreater::createWithholdFundauth($payData);
			} catch (\Exception $e) {
				return apiResponse([],ApiStatus::CODE_50004);
			}
		} 
		
		//-+--------------------------------------------------------------------
		// | 获取并返回url
		//-+--------------------------------------------------------------------
		try{
			$paymentUrl = $pay->getCurrentUrl($params['channel_id'], [
					'name'=>'订单' .$params['order_no']. '支付',
					'front_url' => $params['callback_url'],
			]);
			return apiResponse(['url'=>$paymentUrl['url']],ApiStatus::CODE_0);
		} catch (\Exception $exs) {
            return apiResponse([],ApiStatus::CODE_50004);
		}
    }

    /**
     * 支付宝初始化接口
     * $params[
     *      'return_url'=>'',//前端回调地址
     *      'order_no'=>'', //订单编号
     * ]
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function alipayInitialize(){

 //       $params =$request->all();
        $params['params']=[
            'return_url' =>'http://www.baidu.com',
            'order_no' =>'A531153474749290',
            'user_id' =>'18',
        ];
        $rules = [
            'return_url'  => 'required',
            'order_no'  => 'required',
            'user_id'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
        $res= $this->orderTrade->alipayInitialize($params);
        return $res;
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50004);
        }
        return apiResponse($res,ApiStatus::CODE_0);
        die;
    }

    /**
     * 支付宝资金预授权接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function alipayFundAuth(Request $request)
    {
        $params =$request->all();
//        $params['params']=[
//            'return_url' =>'http://www.baidu.com',
//            'order_no' =>'A528100728283349',
//            'user_id' =>'18',
//        ];
        $rules = [
            'return_url'  => 'required',
            'order_no'  => 'required',
            'user_id'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
        $res= $this->orderTrade->alipayFundAuth($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50004);
        }
        return apiResponse($res,ApiStatus::CODE_0);
        die;

    }


}
