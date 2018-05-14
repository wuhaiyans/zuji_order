<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Warehouse\Modules\Service\DeliveryImeiService;
use App\Warehouse\Modules\Service\DeliveryCreater;
use App\Warehouse\Modules\Service\DeliveryService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{

    use ValidatesRequests;

    const SESSION_ERR_KEY = 'delivery.error';

    protected $DeliveryCreate;

    protected $delivery;

    public function __construct(DeliveryCreater $DeliveryCreate, DeliveryService $delivery)
    {
        $this->DeliveryCreate = $DeliveryCreate;
        $this->delivery = $delivery;
    }


    public function deliveryList(){
//        DB::connection('foo');
        echo "收货表列表接口";
    }

    /**
     * 发货单 -- 创建
     */
    public function deliveryCreate(Request $request){
        $request =$request->all();

        $appid =$request['appid'];//获取appid
        $delivery_row['order_no'] =$request['params']['order_no'];//订单编号
        $delivery_row['delivery_detail'] =$request['params']['delivery_detail'];//发货清单

        try {
            $this->DeliveryCreate->confirmation($delivery_row);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);

    }


    /**
     * 配货
     */
    public function matchGoods()
    {
        $rules = [
            'delivery_no' => 'required', //单号
            'serial_no'   => 'required', //序号
            'quantity'    => 'required', //数量
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->matchGoods($params);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * 配货完成
     */
    public function match()
    {
        $rules = ['deliover_no' => 'required'];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->match($params['deliover_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);

    }


    /**
     * 取消发货
     * 订单系统过来的请求
     */
    public function cancel()
    {
        $rules = ['order_no' => 'required'];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->cancel($params['order_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     * 客服取消发货
     */
    public function cancelDelivery()
    {
        $rules = ['delivery_no' => 'required'];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->cancelDelivery($params['delivery_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 签收
     * params[delivery_id, auto=false]
     */
    public function receive()
    {

        $rules = [
            'delivery_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        $auto = isset($params['auto']) ? $params['auto'] : false;

        try {
            $this->delivery->receive($params['delivery_no'], $auto);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * 发货清单
     */
    public function show()
    {
        $rules = [
            'delivery_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $result = $this->delivery->detail($params['delivery_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse($result);
    }



    /**
     * 对应delivery imei列表
     */
    public function imeis()
    {
        $rules = [
            'delivery_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $list = $this->delivery->imeis($params['delivery_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse($list);
    }


    /**
     * 发货
     */
    public function send()
    {
        $rules = [
            'delivery_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->send($params['delivery_no']);
            $order_no = $this->delivery->getOrderNoByDeliveryNo($params['delivery_no']);
            \App\Lib\Warehouse\Delivery::delivery($order_no);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * 修改物流
     */
    public function logistics()
    {
        $rules = [
            'delivery_no'  => 'required',
            'logistics_id' => 'required',//物流渠道
            'logistics_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->logistics($params['delivery_no'], $params['logistics_id'], $params['logistics_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 取消配货 完成后 为待配货状态
     */
    public function cancelMatch()
    {
        $rules = [
            'delivery_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->cancelMatch($params['delivery_no']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * 取消关联imei
     */
    public function delImei(DeliveryImeiService $server)
    {

        $rules = [
            'delivery_no' => 'required',
            'imei'  => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $server->del($params['delivery_no'], $params['imei']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 添加关联imei
     * 配货
     */
    public function addImei(DeliveryImeiService $server)
    {
        $rules = [
            'delivery_no' => 'required',
            'imei'     => 'required',
            'serial_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $server->add($params['delivery_no'], $params['imei'], $params['serial_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }
    /**
     * 列表查询
     */
    public function list()
    {
        $params = $this->_dealParams([]);
        $list = $this->delivery->list($params);
        return \apiResponse($list);
    }



//    /**
//     * 处理传过来的参数
//     */
//    private function _dealParams($rules)
//    {
//        $params = request()->input();
//
//        if (!isset($params['params'])) {
//            return [];
//        }
//
//        if (is_string($params['params'])) {
//            $params = json_decode($params['params'], true);
//        } else if (is_array($params['params'])) {
//            $params = $params['params'];
//        }
//
//        $validator = app('validator')->make($params, $rules);
//
//        if ($validator->fails()) {
//            session()->flash(self::SESSION_ERR_KEY, $validator->errors()->first());
//            return false;
//        }
//
//        return $params;
//    }


}

