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
        $request =$request->all();

        $appid =$request['appid'];//获取appid
        $delivery_row['order_no'] =$request['params']['order_no'];//订单编号
        $delivery_row['delivery_detail'] =$request['params']['delivery_detail'];//发货清单

        $this->DeliveryCreate->confirmation($delivery_row);
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

        $result = Delivery::detail($params['delivery_no']);

        return \apiResponse($result);
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
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
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

