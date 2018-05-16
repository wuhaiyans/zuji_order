<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Warehouse\Models\Imei;
use App\Warehouse\Modules\Service\ReceiveService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiveController extends Controller
{

    use ValidatesRequests;

    const SESSION_ERR_KEY = 'delivery.error';

    protected $DeliveryCreate;

    public function __construct(ReceiveService $service)
    {
        $this->receive = $service;
    }

    /**
     * 列表
     */
    public function list()
    {
        $params = $this->_dealParams([]);

        try {
            $list = $this->receive->list($params);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse($list);
    }

    /**
     * 清单查询
     */
    public function show()
    {
        $rules = [
            'receive_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $result = $this->receive->show($params['receive_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse($result);
    }

    /**
     * 创建
     */
    public function create()
    {
        $rules = [
            'order_no' => 'required',
            'receive_detail' => 'required'
        ];
        $params = $this->_dealParams($rules);

        try {
            $this->receive->create($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 取消收货单
     */
    public function cancel()
    {
        $rules = [
            'receive_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        try {
            $this->receive->cancel($params['receive_no']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 签收
     */
    public function received()
    {
        $rules = [
            'receive_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        try {
            $this->receive->received($params['receive_no']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }


    /**
     * 收货单明细收货
     */
    public function receiveDetail()
    {
        $rules = [
            'receive_no' => 'required',
            'serial_no'  => 'required',
            'quantity'   => 'required'
        ];
        $params = $this->_dealParams($rules);

        try {
            $this->receive->receiveDetail($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 取消签收
     */
    public function cancelReceive()
    {
        $rules = [
            'receive_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        try {
            $this->receive->cancelReceive($params['receive_no']);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }


    /**
     * 验收 针对设备
     */
    public function check()
    {
        $rules = [
            'receive_no' => 'required',
            'serial_no'  => 'required',
            'imei'       => 'required',
            'check_result'       => 'required',
        ];

        $params = $this->_dealParams($rules);

        try {
            $this->receive->check($params['receive_no'],$params['serial_no'], $params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 取消验收 针对设备
     */
    public function cancelCheck()
    {
        $rules = [
            'receive_no' => 'required',
            'serial_no'  => 'required'
        ];

        $params = $this->_dealParams($rules);

        try {
            $this->receive->cancelCheck($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 完成签收 针对收货单
     * 1.状态修改
     * 2.通知订单
     */
    public function finishCheck()
    {
        $rules = [
            'receive_no' => 'required'
        ];

        $params = $this->_dealParams($rules);

        try {
            $receive = $this->receive->finishCheck($params['receive_no']);

            $imeis = $receive->imeis;

            foreach ($imeis as $imei) {
                $imodel = Imei::find($imei->imei);
                $imodel->status = Imei::STATUS_IN;
                $imodel->update();
            }

            \App\Lib\Order\Receive::checkResult($receive->order_no, $imeis->toArray());

        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }


    /**
     * 录入检测项
     */
    public function checkItems()
    {
        $rules = [
            'receive_no' => 'required',
            'serial_no'  => 'required',
            'check_item' => 'required'
        ];

        $params = $this->_dealParams($rules);

        try {
            $this->receive->checkItems($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
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

