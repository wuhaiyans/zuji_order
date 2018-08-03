<?php
/**
 *  订单清算数据
 *   heaven
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\Pay\Channel;
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
    public function cleanList(Request $request){
//        LogApi::info('订单清算列表接口调用参数：', $request->all());
        $params = $request->input('params');

        $res = OrderCleaning::getOrderCleaningList($params);

        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }


    /**
     *
     * 清算列表过滤筛选列表接口
     * Author: heaven
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function orderCleaningListFilter()
    {

        $res = \App\Order\Modules\Inc\OrderCleaningListFiler::orderCleanInc();
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
//        LogApi::info('订单详情接口调用参数：', $params);
        $rules = [
            'clean_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $res = OrderCleaning::getOrderCleanInfo($params['params']);


        return $res;

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


        return apiResponse([],$res);

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
        $business_no = 'A530177589116734';
        $pay = null;
        try {
            // 查询
            $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);
            // 取消
//            $pay->cancel();
//            // 恢复
//            $pay->resume();

        } catch (\App\Lib\NotFoundException $exc) {

            // 创建支付
            $pay = \App\Order\Modules\Repository\Pay\PayCreater::createPayment([
                'user_id'		=> '5',
                'businessType'	=> $business_type,
                'businessNo'	=> $business_no,

                'paymentAmount' => '0.01',
                'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Jdpay,
                'paymentFenqi'	=> 0,

                'withholdChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,

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
                'front_url'		=> config('ordersystem.ORDER_API').'/order/pay/testPaymentFront',	//【必选】string 前端回跳地址
            ];

            $pay->setPaymentAmount(0.01);

            $url_info = $pay->getCurrentUrl( \App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
            header( 'Location: '.$url_info['url'] );
//			var_dump( $url_info );

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


//        $json = '{"notify_app_id":"2018032002411058","out_order_no":"A803194614389933","notify_type":"ZM_RENT_ORDER_CANCEL","channel":"rent","zm_order_no":"2018080300001001094675819027","sign":"ccNyRRFmpnvdX6C70pClFBY6xisjS+OiTs8KpY8NGXvgjLVoL6gmw+bjA8eFGM3dd0z6papJ2ep1rbtUcVJPtso0eGb2BcPBx4CcVcIBGRtFWxOlTWvTDsf3KnaFw0J2\/xM9TezWWtT5uDyC+YNEbG9pYuT9YdUXKMvG23heWe5AwYdTx62uj1Sgcv5PoeT1UKV2qCMdXiVIzp1xYXQvGyfd515NVw5YOMkyrfK3Frw7WRgln\/Rgua+JjT0hRYZgdoGC2ruXcx\/PevhkJArhixsxUGjw6aTlN0JHHyryYDyIoEFW8xHuXAKtQ232qwJt+\/swuEdn1RriyVYBN40h8g==","sign_type":"RSA2"}';
//        OrderCleaning::miniUnfreezeAndPayClean(json_decode($json,true));

        try {
            $params = $request->all();
            $rules = [
                'clean_no'=> 'required'
            ];
            $validateParams = $this->validateParams($rules,$params);
            if ($validateParams['code']!=0) {

                return apiResponse([],$validateParams['code']);
            }

            $res = OrderCleaning::orderCleanOperate($params);

            if ($res['code']==0) return apiResponse([],ApiStatus::CODE_0,"success");
            return apiResponse([],ApiStatus::CODE_31202,$res['msg']);


        } catch(\Exception $e)
        {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }


    }





}
