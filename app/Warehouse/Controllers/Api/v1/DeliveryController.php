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
            'delivery_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            Delivery::finishMatch($params['delivery_no']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
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
            Delivery::cancelMatch($params['delivery_no']);
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
        $params = $this->_dealParams([]);

        $limit = 20;
        if (isset($params['limit']) && $params['limit']) {
            $limit = $params['limit'];
        }

        $whereParams = [];

        if (isset($params['order_no']) && $params['order_no']) {
            $whereParams['order_no'] = $params['order_no'];
        }

        if (isset($params['delivery_no']) && $params['delivery_no']) {
            $whereParams['delivery_no'] = $params['delivery_no'];
        }

        $list = Delivery::where($whereParams)->paginate($limit);

        $d = $list->toArray();
        $result = [
            'data' => $d['data'],
            'current_page' => $d['current_page'],
            'last_page' => $d['last_page'],
            'per_page' => $d['per_page'],
            'total' => $d['total']
        ];

        return \apiResponse($result);

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

        $result = Delivery::detail($params['delivery_no']);

        return \apiResponse($result);
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
            'delivery_no' => 'required',
            'logistics_id' => 'required',//物流渠道
            'logistics_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            Delivery::logistics($params['delivery_no'], $params['logistics_id'], $params['logistics_no']);
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
            'delivery_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $model = Delivery::findOrFail($params['delivery_no']);
            $list = $model->imeis;
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
     * params[delivery_no, auto=false]
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
            Delivery::receive($params['delivery_no'], $auto);
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
            'delivery_no' => 'required',
            'imei'  => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            if (!DeliveryGoodsImei::del($params['delivery_no'], $params['imei'])) {
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
            'delivery_no' => 'required',
            'imeis'  => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_20001, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            if (!DeliveryGoodsImei::add($params['delivery_no'], $params['imeis'])) {
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

        if (!isset($params['params'])) {
            session()->flash(self::SESSION_ERR_KEY, '参数不完整');
            return false;
        }

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

