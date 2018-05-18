<?php
/**
 *  队列消费回调数据处理接口
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/5/18 0018
 * Time: 下午 2:18
 */
namespace App\Order\Controllers\Api\v1;
use Illuminate\Http\Request;
class InnerServiceController extends Controller
{

    /**
     * 队列处理成功，返回该函数
     * @param int $type
     * @return string
     */
    protected function innerOkMsg(){
        $returnData = array('status'=>'ok');
        return json_encode($returnData);

    }

    /**
     *
     * 消费处理失败，返回该函数，处理失败，队列可能会开启重试机制
     * @param int $type
     * @return string
     */
    protected function innerErrMsg(){
        $returnData = array('status'=>'error');
        return json_encode($returnData);

    }

    /**
     * 订单取消处理接口
     * heaven
     * @param order_no 订单编号
     *
     */
    public function cancelOrder(Request $request)
    {
        $params = $request->all();
        $rules = [
            'order_no'  => 'required',
            'user_id'  => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $success =   \App\Order\Modules\Service\OrderOperate::cancelOrder($params['order_no'], $params['user_id']);
        if ($success) {

                return $this->innerErrMsg();
        }
        return $this->innerOkMsg();

    }
















}