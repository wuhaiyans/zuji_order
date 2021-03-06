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
use App\Lib\PublicInc;
use App\Order\Modules\Service\OrderBlock;
use App\Order\Modules\Service\OrderBuyout;
use App\Order\Modules\Service\OrderOperate;
use Illuminate\Http\Request;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Inc\OrderFreezeStatus;
class InnerServiceController extends Controller
{

    /**
     * 订单发货生成合同
     * @author wuhaiyan
     * @param order_no 订单编号
     * @param user_id 用户ID
     *
     */
    public function DeliveryApply(Request $request)
    {
        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'InnerService-DeliveryApply-info:'.$input);
        $params = json_decode($input,true);

        $rules = [
            'user_id'  => 'required',
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $success =   \App\Order\Modules\Service\OrderOperate::DeliveryApply($validateParams['data']['order_no'],$validateParams['data']['user_id']);
        if ($success) {

            return $this->innerErrMsg(ApiStatus::$errCodes[$success]);
        }
        return $this->innerOkMsg();

    }

    /**
     * 订单发货生成合同
     * @author wuhaiyan
     * @param order_no 订单编号
     * @param user_id 用户ID
     *
     */
    public function DeliveryContract(Request $request)
    {

        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'InnerService-DeliveryContract-info:'.$input);
        $params = json_decode($input,true);

        $rules = [
            'user_id'  => 'required',
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $success =   \App\Order\Modules\Service\OrderOperate::DeliveryContract($validateParams['data']['order_no'],$validateParams['data']['user_id']);
        if ($success) {

            return $this->innerErrMsg(ApiStatus::$errCodes[$success]);
        }
        return $this->innerOkMsg();

    }
    /**
     * 订单风控信息存储
     * @author wuhaiyan
     * @param order_no 订单编号
     * @param user_id 用户ID
     *
     */
    public function YajinReduce(Request $request)
    {

        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'InnerService-OrderRisk-YajinReduce-info:'.$input);
        $params = json_decode($input,true);

        $rules = [
            'user_id'  => 'required',
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $success =   \App\Order\Modules\Service\OrderOperate::YajinReduce($validateParams['data']['order_no'],$validateParams['data']['user_id']);
        if ($success) {

                return $this->innerErrMsg(ApiStatus::$errCodes[$success]);
        }
        return $this->innerOkMsg();

    }
    /**
     * 订单风控信息存储
     * @author wuhaiyan
     * @param order_no 订单编号
     * @param user_id 用户ID
     *
     */
    public function orderRisk(Request $request)
    {

        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'InnerService-OrderRisk-save-info:'.$input);
        $params = json_decode($input,true);

        $rules = [
            'user_id'  => 'required',
            'order_no'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $success =   \App\Order\Modules\Service\OrderOperate::orderRiskSave($validateParams['data']['order_no'],$validateParams['data']['user_id']);
        if ($success) {

            return $this->innerErrMsg(ApiStatus::$errCodes[$success]);
        }
        return $this->innerOkMsg();

    }
    /**
     * 订单取消处理接口
     * heaven
     * @param order_no 订单编号
     *
     */
    public function cancelOrder(Request $request)
    {

        $input = file_get_contents("php://input");
        LogApi::info(__METHOD__.'() '.microtime(true).'InnerService-cancelOrder-info:'.$input);
        $params = json_decode($input,true);

        $rules = [
            'order_no'  => 'required',
            'user_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $userinfo =[
            'uid'=>$validateParams['data']['user_id'],
            'username'=>'system',
            'type'=>PublicInc::Type_System,
        ];

        //查询订单是否在冻结状态
        $orderObj = new OrderRepository();
        $orderInfo = $orderObj->get_order_info(['order_no'=>$validateParams['data']['order_no']]);
        if( !$orderInfo || $orderInfo[0]['freeze_type'] ){
            $msg = '订单处于'.OrderFreezeStatus::getStatusName($orderInfo[0]['freeze_type']) . '中，禁止取消！';
            return apiResponse([],ApiStatus::CODE_35024,$msg);
        }

        $success =   \App\Order\Modules\Service\OrderOperate::cancelOrder($validateParams['data']['order_no'],$userinfo);
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

        LogApi::info(__METHOD__.'() '.microtime(true).'InnerService-deliveryReceive-info:'.$input);
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

        LogApi::info(__METHOD__.'() '.microtime(true).'小程序订单取消处理接口消费处理参数:'.$input);
        $params = json_decode($input,true);
        $rules = [
            'order_no'  => 'required',
            'user_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        //查询订单是否在冻结状态
        $orderObj = new OrderRepository();
        $orderInfo = $orderObj->get_order_info(['order_no'=>$validateParams['data']['order_no']]);
        if( !$orderInfo || $orderInfo[0]['freeze_type'] ){
            $msg = '订单处于'.OrderFreezeStatus::getStatusName($orderInfo[0]['freeze_type']) . '中，禁止取消！';
            return apiResponse([],ApiStatus::CODE_35024,$msg);
        }

        //调用小程序取消订单接口
        //查询芝麻订单
        $result = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($validateParams['data']['order_no']);
        if( empty($result) ){
            \App\Lib\Common\LogApi::info('本地小程序查询芝麻订单信息表失败',$validateParams['data']['order_no']);
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
            \App\Lib\Common\LogApi::info('小程序订单取消失败（30分钟定时取消）',\App\Lib\Payment\mini\MiniApi::getError());
            return apiResponse(['reason'=>\App\Lib\Payment\mini\MiniApi::getError()],ApiStatus::CODE_35005);
        }


        $userinfo =[
            'uid'=>$validateParams['data']['user_id'],
            'username'=>'system',
            'type'=>PublicInc::Type_System,
        ];
        $success =   \App\Order\Modules\Service\OrderOperate::cancelOrder($validateParams['data']['order_no'], $userinfo);
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
        $params = json_decode($input,true);
        LogApi::info(__METHOD__.'() '.microtime(true).'买断单取消接口:'.json_encode($params));

        //过滤参数
        $rule= [
            'buyout_no'=>'required',
            'user_id'=>'required',
        ];
        $validator = $this->validateParams($rule,$params);
        if ($validator['code']!=0) {
            return $this->innerErrMsg($validator['code']);
        }

        $ret = OrderBuyout::cancel($params);

        if(!$ret){
            return $this->innerErrMsg("取消买断单失败");
        }
        return $this->innerOkMsg();
    }
    /*
     * 区块链推送
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "buyout_no"=>"",买断业务号
     * ]
     * @return json
     */
    public function orderPushBlock(){
        $input = file_get_contents("php://input");
        $params = json_decode($input,true);

        $ret = OrderBlock::main($params['order_no'],$params['order_block_node'],$params['block_data']);

        if($ret!=0){
            $params['code'] = $ret;
            LogApi::info(__METHOD__.'() '.microtime(true).'OrderBlock区块链推送失败:'.json_encode($params));
        }
        return $this->innerOkMsg();
    }











}