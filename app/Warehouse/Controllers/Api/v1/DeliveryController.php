<?php

namespace App\Warehouse\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Warehouse\Models\Delivery;
use App\Warehouse\Modules\Service\DeliveryImeiService;
use App\Warehouse\Modules\Service\DeliveryCreater;
use App\Warehouse\Modules\Service\DeliveryService;
use App\Warehouse\Modules\Service\ReceiveGoodsService;
use App\Warehouse\Modules\Service\ReceiveService;
use Illuminate\Support\Facades\Log;
use App\Warehouse\Config;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class DeliveryController extends Controller
{
    protected $DeliveryCreate;

    protected $delivery;

    public function __construct(DeliveryCreater $DeliveryCreate, DeliveryService $delivery)
    {
        $this->DeliveryCreate = $DeliveryCreate;
        $this->delivery = $delivery;
    }

    /**
     * 发货单 -- 创建
     *
     * order_no 订单号
     * delivery_detail 设备明细
     */
    public function deliveryCreate(){
        $rules = [
            'order_no' => 'required', //单号
            'delivery_detail'   => 'required', //序号
        ];

        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->DeliveryCreate->confirmation($params);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }


    /**
     * 单件配货
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function matchGoods()
    {
        /**
         * $params 数组中需要包含以下字段
         * delivery_no 发货单号
         * serial_no 设备序号
         * quantity  设备数量
         */
        $rules = [
            'delivery_no' => 'required', //单号
            'goods_no'   => 'required', //序号
            'quantity'    => 'required', //数量
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
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
     *
     */
    public function match()
    {
        //delivery_no 发货单号
        $rules = ['delivery_no' => 'required'];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->match($params['delivery_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);

    }


    /**
     * 取消发货 订单系统过来的请求
     *
     * order_no 订单号
     */
    public function cancel()
    {

        $rules = ['order_no' => 'required'];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
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
        $rules = ['delivery_no' => 'required'];//delivery_no 发货单号
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
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
     * params[delivery_id, receive_type=1]
     */
    public function receive()
    {
        $rules = [
            'delivery_no' => 'required',
            'receive_type'=> 'required',
            'user_id'=> 'required',
            'user_name'=> 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        $receive_type = isset($params['receive_type']) ? $params['receive_type'] : false;

        try {
            $deliveryInfo = $this->delivery->receive($params['delivery_no'], $receive_type);

            \App\Lib\Warehouse\Delivery::receive($deliveryInfo['order_no'], ['receive_type'=>$params['receive_type'],'user_id'=>$params['user_id'],'user_name'=>$params['user_name']]);

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
        $rules = [//delivery_no 发货单号
            'delivery_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
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
        $rules = [//delivery_no 发货单号
            'delivery_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
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
        $rules = [//delivery_no 发货单号
            'delivery_no' => 'required',
            'logistics_id' => 'required',//物流渠道
            'logistics_no' => 'required',
            'logistics_note' => 'required',
            'user_id' => 'required',
            'user_name' => 'required',
            'type'=> 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->send($params);
            $result = $this->_info($params['delivery_no']);

            $orderDetail = [
                'order_no' => $result['order_no'],
                'logistics_id' => $params['logistics_id'],
                'logistics_no' => $params['logistics_no'],
            ];
            //操作员信息,用户或管理员操作有
            $user_info['user_id'] = $params['user_id'];
            $user_info['user_name'] = $params['user_name'];
            $user_info['type'] = $params['type'];

            //通知订单接口
            \App\Lib\Warehouse\Delivery::delivery($orderDetail, $result['goods_info'], $user_info);
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
        /**
         * delivery_no 发货单号
         * logistics_id 物流渠道
         * logistics_no 物流编号
         */
        $rules = [
            'delivery_no'  => 'required',
            'logistics_id' => 'required',//物流渠道
            'logistics_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->logistics($params);
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
        $rules = [ //delivery_no 发货单号
            'delivery_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
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
     * 取消配货 完成后 为待配货状态
     */
    public function cancelMatchGoods()
    {
        $rules = [ //delivery_no 发货单号
            'delivery_no' => 'required',
            'goods_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->delivery->cancelMatchGoods($params);
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

        /**
         * delivery_no 发货单号
         * imei 设备imei
         */
        $rules = [
            'delivery_no' => 'required',
            'imei'  => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
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
        /**
         * delivery_no 发货单号
         * imei 设备imei
         * serial_no 设备序号
         */
        $rules = [
            'delivery_no' => 'required',
            'imei'      => 'required',
            'goods_no' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $server->add($params);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }
    /**
     * 列表查询
     *
     * 可按创建时间，发货时间，订单号等状态查询
     */
    public function list()
    {
        $params = $this->_dealParams([]);
        $list = $this->delivery->list($params);
        return \apiResponse($list);
    }


    /**
     * 导出excel
     */
    public function export()
    {
        $params = $this->_dealParams([]);

        $params['detail'] = true;

        $list = $this->delivery->list($params);

        $data = [];
        foreach ($list['data'] as $l) {
            if (!$l['goods']) continue;
            foreach ($l['goods'] as $g) {
                $data[] = [
                    'order_no' => $l['order_no'],
                    'customer' => $l['customer'],
                    'customer_mobile' => $l['customer_mobile'],
                    'customer_address' => $l['customer_address'],

                    'logistics_no' => $l['logistics_no'],
                    'status' => Delivery::sta($l['status']),
                    'goods_name' => $g['goods_name'],
                    'price' => isset($g['price']) ? $g['price'] : 0.00
                ];
            }
        }
        dd($data);
    }


    /**
     * @param null $logisticsId
     * @return array|string
     *
     * 取快递名
     */
    public function logisticName()
    {

        $rules = [
            'logistics_id' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        $name = $this->delivery->getLogistics($params['logistics_id']);
        return \apiResponse(['name'=>$name]);
    }

    /**
     * @return mixed
     * 取列表
     */
    public function logisticList()
    {
        $list = $this->delivery->getLogistics();
        return apiResponse(['list'=>$list]);
    }


    /**
     *
     */
    public function publics()
    {
        $data = [
            'status_list' => Delivery::sta(),
            'kw_types'    => DeliveryService::searchKws()
        ];
        return apiResponse($data);
    }



    public function statistics()
    {
        return apiResponse([
            'delivery' => DeliveryService::statistics(),
            'receive'   => ReceiveService::statistics(),
            'check'     => ReceiveGoodsService::statistics()
        ]);
    }


    protected function _info($delivery_no)
    {
        $model = Delivery::where(['delivery_no'=>$delivery_no])->first();

        if (!$model) {
            throw new NotFoundResourceException('发货单未找到');
        }

        $goods = $model->goods;
        $imeis = $model->imeis->toArray();

        if (!$goods) {
            throw new NotFoundResourceException('设备信息未找到');
        }
        $result = [];
        foreach ($goods as $g) {
            $result[$g->goods_no]['goods_no'] = $g->goods_no;
            if (is_array($imeis)) {
                $i=1;
                foreach ($imeis as $imei) {
                    if ($imei['goods_no'] == $g->goods_no) {
                        $result[$g->goods_no]['imei' . $i] = $imei['imei'];
                        $result[$g->goods_no]['serial_number'] = $imei['apple_serial'];
                        $i++;
                    }
                }
            }
        }

        return ['order_no'=>$model->order_no, 'goods_info'=>$result];
    }





}

