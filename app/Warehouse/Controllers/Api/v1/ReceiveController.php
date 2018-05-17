<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Warehouse\Models\Imei;
use App\Warehouse\Modules\Service\ReceiveService;

class ReceiveController extends Controller
{
    const SESSION_ERR_KEY = 'delivery.error';

    protected $DeliveryCreate;

    public function __construct(ReceiveService $service)
    {
        $this->receive = $service;
    }

    /**
     * 收货单列表
     *
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
     * 收货单清单查询
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
     * 收货单创建
     */
    public function create()
    {
        $rules = [
            'order_no' => 'required',
            'receive_detail' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

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

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

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

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

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
        /**
         * receive_no 收货单号
         * serial_no 设备序号
         * quantity 设备数量
         */
        $rules = [
            'receive_no' => 'required',
            'serial_no'  => 'required',
            'quantity'   => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

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

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

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
            'check_result'  => 'required', //针对设备的检测结果
        ];

        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->receive->check($params['receive_no'],$params['serial_no'], $params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 已验收的设备 取消验收 针对设备
     */
    public function cancelCheck()
    {
        $rules = [
            'receive_no' => 'required',//收货单编号
            'serial_no'  => 'required' //设备序号
        ];

        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

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

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

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

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->receive->checkItems($params);
        } catch (\Exception $e) {
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

}

