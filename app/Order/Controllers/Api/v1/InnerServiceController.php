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
















}