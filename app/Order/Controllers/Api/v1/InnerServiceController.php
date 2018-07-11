<?php
/**
 *  队列消费回调数据处理接口
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/5/18 0018
 * Time: 下午 2:18
 */
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Order\Modules\Service\OrderBuyout;
use App\Order\Modules\Service\OrderOperate;
use Illuminate\Http\Request;
class InnerServiceController extends Controller
{


    /**
     * 订单取消处理接口
     * heaven
     * @param order_no 订单编号
     *
     */
    public function cancelOrder(Request $request)
    {

        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'订单取消处理接口消费处理参数:'.$input);
        $params = json_decode($input,true);

        $rules = [
            'order_no'  => 'required',
            'user_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $success =   \App\Order\Modules\Service\OrderOperate::cancelOrder($validateParams['data']['order_no'], $validateParams['data']['user_id']);
        if ($success) {

                return $this->innerErrMsg(ApiStatus::$errCodes[$success]);
        }
        return $this->innerOkMsg();

    }

    /**
     * 确认收货接口
     * @param Request $request
     * $params
     * [
     *  'order_no' =>'',//订单编号
     * ]
     * @return \Illuminate\Http\JsonResponse
     */

    public function deliveryReceive(Request $request)
    {
        $input = file_get_contents("php://input");

        LogApi::info(__METHOD__.'() '.microtime(true).'订单确认收货费处理参数:'.$input);
        $params = json_decode($input,true);
        $rules = [
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $res = OrderOperate::deliveryReceive($validateParams['data'],1);
        if(!$res){
            return $this->innerErrMsg(ApiStatus::$errCodes[ApiStatus::CODE_30012]);
        }
        return $this->innerOkMsg();
    }



    /**
     * 小程序订单取消处理接口
     * heaven
     * @param order_no 订单编号
     *
     */
    public function miniCancelOrder(Request $request)
    {

        $input = file_get_contents("php://input");

        LogApi::info(__METHOD__.'() '.microtime(true).'订单取消处理接口消费处理参数:'.$input);
        $params = json_decode($input,true);
        $rules = [
            'order_no'  => 'required',
            'user_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        //调用小程序取消订单接口
        //查询芝麻订单
        $result = \App\Order\Modules\Repository\MiniOrderRepository::getMiniOrderInfo($params['order_no']);
        if( empty($result) ){
            \App\Lib\Common\LogApi::info('本地小程序查询芝麻订单信息表失败',$params['order_no']);
            return apiResponse([],ApiStatus::CODE_35003,'本地小程序查询芝麻订单信息表失败');
        }
        //发送取消请求
        $data = [
            'out_order_no'=>$result['order_no'],//商户端订单号
            'zm_order_no'=>$result['zm_order_no'],//芝麻订单号
            'app_id'=>$result['app_id'],//小程序appid
        ];
        $b = \App\Lib\Payment\mini\MiniApi::OrderCancel($data);
        if($b === false){
            \App\Lib\Common\LogApi::info('小程序订单取消失败（芝麻）',\App\Lib\Payment\mini\MiniApi::getError());
            return apiResponse(['reason'=>\App\Lib\Payment\mini\MiniApi::getError()],ApiStatus::CODE_35005);
        }

        $success =   \App\Order\Modules\Service\OrderOperate::cancelOrder($validateParams['data']['order_no'], $validateParams['data']['user_id']);
        if ($success) {

            return $this->innerErrMsg(ApiStatus::$errCodes[$success]);
        }
        return $this->innerOkMsg();

    }
    /*
     * 取消买断
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "buyout_no"=>"",买断业务号
     * ]
     * @return json
     */
    public function cancelOrderBuyout(){
        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'订单取消处理接口消费处理参数:'.$input);
        $params = json_decode($input,true);
        //过滤参数
        $rule= [
            'buyout_no'=>'required',
            'user_id'=>'required',
        ];
        $validator = $this->validateParams($params, $rule);
        if ($validator['code']!=0) {
            return $this->innerErrMsg($validator['code']);
        }

        $ret = OrderBuyout::cancel($params);

        if(!$ret){
            return $this->innerErrMsg("取消买断单失败");
        }
        return $this->innerOkMsg();
    }











}