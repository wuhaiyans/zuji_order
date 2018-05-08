<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiResponse;
use App\Lib\ApiStatus;
use App\Warehouse\Models\Delivery;
use App\Warehouse\Models\DeliveryGoodsImei;
use App\Warehouse\Modules\Service\DeliveryCreater;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{

    use ValidatesRequests;

    const SESSION_ERR_KEY = 'delivery.error';
    protected $DeliveryCreate;

    public function __construct(DeliveryCreater $DeliveryCreate)
    {
        $this->DeliveryCreate = $DeliveryCreate;
    }


    public function deliveryList(){
//        DB::connection('foo');
        echo "收货表列表接口";
    }

    /**
     * 创建发货单
     */
    public function deliveryCreate(Request $request){
        $orders =$request->all();

        $appid =$orders['appid'];//获取appid
        $order_no =$orders['params']['order_no'];//订单编号
        $delivery_detail =$orders['params']['delivery_detail'];//发货清单

        $delivery_row = [
//            'delivery_no'=>
        ];

    }


    /**
     * 配货完成
     */
    public function finishMatch()
    {
        $rules = [
            'delivery_id' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            Delivery::finishMatch($params['delivery_id']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 取消配货
     */
    public function cancelMatch()
    {
        $rules = [
            'delivery_id' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            Delivery::cancelMatch($params['delivery_id']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 列表查询
     */
    public function list()
    {

    }

    /**
     * 发货清单
     */
    public function show()
    {

    }

    /**
     * 发货
     */
    public function send()
    {
        $rules = [
            'order_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            Delivery::send($params['order_no']);

            \App\Lib\Warehouse\Delivery::delivery($params['order_no']);

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
            'delivery_id' => 'required',
            'logistics_id' => 'required',//物流渠道
            'logistics_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            Delivery::logistics($params['delivery_id'], $params['logistics_id'], $params['logistics_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }




    /**
     * 对应delivery imei列表
     */
    public function imeis()
    {
        $rules = [
            'delivery_id' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $list = Delivery::imeis($params['delivery_id']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse($list);
    }

    /**
     * 取消发货
     */
    public function cancel()
    {

        $rules = [
            'order_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            if (!Delivery::cancel($params['order_no'])) {
                return \apiResponse([], ApiStatus::CODE_60002, '取消未成功');
            }
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
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
            'delivery_id' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        $auto = isset($params['auto']) ? $params['auto'] : false;

        try {
            Delivery::receive($params['delivery_id'], $auto);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 取消关联imei
     */
    public function delImei()
    {

        $rules = [
            'delivery_id' => 'required',
            'imei'  => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            if (!DeliveryGoodsImei::del($params['delivery_id'], $params['imei'])) {
                return \apiResponse([], ApiStatus::CODE_60002, '删除imei失败');
            }
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 添加关联imei
     */
    public function addImei()
    {
        $rules = [
            'delivery_id' => 'required',
            'imei'  => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            if (!DeliveryGoodsImei::add($params['delivery_id'], $params['imei'])) {
                return \apiResponse([], ApiStatus::CODE_60002, '添加imei失败');
            }
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * 处理传过来的参数
     */
    private function _dealParams($rules)
    {
        $params = request()->input();

        if (is_string($params['params'])) {
            $params = json_decode($params['params'], true);
        } else if (is_array($params['params'])) {
            $params = $params['params'];
        }

        $validator = app('validator')->make($params, $rules);

        if ($validator->fails()) {
            session()->flash(self::SESSION_ERR_KEY, $validator->errors()->first());
            return false;
        }

        return $params;
    }


}

