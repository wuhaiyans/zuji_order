<?php
/**
 *  订单清算数据
 *   heaven
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\Pay\PayCreater;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderCleaning;
use Illuminate\Support\Facades\Log;

class OrderCleaningController extends Controller
{


    /**
     *
     * 订单清算列表查询
     * Author: heaven
     * @param Request $request
     *  params": - {
                    "page":"1",                //类型：String  必有字段  备注：页码
                    "status":"mock",                //类型：String    备注：出账状态
                    "begin_time":1,                //类型：Number   备注：开始时间
                    "end_time":1,                //类型：Number    备注：结束时间
                    "app_id":1,                //类型：Number    备注：入账来源
                    "out_account":"mock",                //类型：String    备注：出账方式
                    "order_no":"mock"                //类型：String    备注：订单号
             }
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request){

        $params = $request->input('params');
        $res = OrderCleaning::getOrderCleaningList($params);

        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }



    /**
     * 订单结算详情查询
     * Author: heaven
     * @param Request $request
     *  params": - {
                "business_type":"mock",    //类型：String  必有字段  备注：业务类型
                "business_no":"mock"       //类型：String  必有字段  备注：业务编号
        }
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request){

        $params = $request->all();

        $rules = [
            'clean_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $res = OrderCleaning::getOrderCleanInfo($params['params']);


        return apiResponse($res,ApiStatus::CODE_0,"success");

    }



    /**
     * 订单清算取消接口
     * Author: heaven
     * @param Request $request
     * params": - {
            "business_type":"mock",    //类型：String  必有字段  备注：业务类型
                "business_no":"mock"       //类型：String  必有字段  备注：业务编号
        }
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrderClean(Request $request){

        $params = $request->all();

        $rules = [
            'clean_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::cancelOrderClean($params['params']);
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }


    /**
     *
     * 订单清算更新状态
     * Author: heaven
     * @param Request $request
     * params": - {
        "business_type":"mock",    //类型：String  必有字段  备注：业务类型
        "business_no":"mock"       //类型：String  必有字段  备注：业务编号
        }
     * @return \Illuminate\Http\JsonResponse
     */
    public function upOrderCleanStatus(Request $request){

        $params = $request->all();

        $rules = [
            'clean_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::upOrderCleanStatus($validateParams['data']);
        return apiResponse([] , $res);

    }


    /**
     *
     * 创建订单清单
     * Author: heaven
     * @param Request $request
     * params": - {
        "business_type":"mock",    //类型：String  必有字段  备注：业务类型
        "business_no":"mock"       //类型：String  必有字段  备注：业务编号
     }
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrderClean(Request $request)
    {

        $params = $request->all();

        $rules = [
            'business_type'  => 'required',
            'business_no'  => 'required',
            'order_no'   => 'required'
        ];

        $validateParams = $this->validateParams($rules,$params);

        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::createOrderClean($params['params']);
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }


    /**
     * 支付测试
     * Author: heaven
     * @throws \ErrorException
     */
    public function testPay()
    {

        $business_type = 1;
        $business_no = 'A528197713276854'; //\createNo(1);
        $pay = null;
        try {
            // 查询
            $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);

            // 取消
            //$pay->cancel();
            // 恢复
            //$pay->resume();

        } catch (\App\Lib\NotFoundException $exc) {

            // 创建支付
            $pay = PayCreater::createPaymentWithholdFundauth([
                'user_id'		=> '5',
                'businessType'	=> $business_type,
                'businessNo'	=> $business_no,

                'paymentNo' => \createNo(1),
                'paymentAmount' => '0.01',
                'paymentChannel'=> Channel::Alipay,
                'paymentFenqi'	=> 0,

                'withholdNo' => \createNo(1),
                'withholdChannel'=> Channel::Alipay,

                'fundauthNo' => \createNo(1),
                'fundauthAmount' => '1.00',
                'fundauthChannel'=> Channel::Alipay,
            ]);
        } catch (\Exception $exc) {
            exit('error');
        }

//        // 支付阶段状态
//        $this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//
//
//        // 支付状态
//        $this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
//            '支付环节状态初始化错误' );
//
//        // 代扣签约状态
//        $this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::WAIT_WITHHOLD,
//            '代扣签约状态初始化错误' );
//
//        // 资金预授权状态
//        $this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::WAIT_FUNDAUTH,
//            '资金预授权状态初始化错误' );

        try {
            $step = $pay->getCurrentStep();
            echo '当前阶段：'.$step."\n";

            $_params = [
                'name'			=> '测试支付',					//【必选】string 交易名称
                'back_url'		=> 'https://alipay/Test/back',	//【必选】string 后台通知地址
                'front_url'		=> 'https://alipay/Test/front',	//【必选】string 前端回跳地址
            ];
            $url_info = $pay->getCurrentUrl( 2,$_params );
            p($url_info['url']);
            header("Location: {$url_info['url']}");
//            return redirect($url_info['url']);

        } catch (\Exception $exc) {
            echo $exc->getMessage()."\n";
            echo $exc->getTraceAsString();
        }


    }

    /**
     * 订单清算出帐
     * Author: heaven
     * @param Request $request
     * params": - {
        "business_type":"1",                //类型：String  必有字段  备注：业务类型
        "business_no":"TA51740879563943",    //类型：String  必有字段  备注：业务编号
        "out_refund_no":"CA51823429373928"    //类型：String  必有字段  备注：业务平台退款码
    }
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderCleanOperate(Request $request)
    {

        try {


//            $this->testPay();
//            exit;

            $params = $request->all();

            $rules = [
                'clean_no'=> 'required'
            ];
            $validateParams = $this->validateParams($rules,$params);
            if ($validateParams['code']!=0) {

                return apiResponse([],$validateParams['code']);
            }

            $res = OrderCleaning::orderCleanOperate($params['params']);
            if (!$res) return apiResponse($res,ApiStatus::CODE_0,"success");
            return apiResponse([],ApiStatus::CODE_31202);

        } catch(\Exception $e)
        {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }


    }





}
