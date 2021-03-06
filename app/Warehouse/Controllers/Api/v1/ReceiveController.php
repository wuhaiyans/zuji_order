<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\TencentUpload;
use App\Lib\Warehouse\Receive;
use App\Warehouse\Models\Imei;
use App\Warehouse\Models\ReceiveGoods;
use App\Warehouse\Modules\Repository\ImeiRepository;
use App\Warehouse\Modules\Service\ReceiveService;
use App\Warehouse\Modules\Service\WarehouseWarning;
use Illuminate\Support\Facades\DB;


/**
 * Class ReceiveController
 * @package App\Warehouse\Controllers\Api\v1
 */
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
        $request = request()->input();

        try {
            $params['channel_id'] = json_decode($request['userinfo']['channel_id'], true);
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
            'logistics_id' => 'required',
            'logistics_no' => 'required',
            'type' => 'required',
            'business_key' => 'required',
            'customer' => 'required',
            'customer_mobile' => 'required',
            'customer_address' => 'required',
            'receive_detail' => 'required',
            'channel_id' => 'required',
            'appid' => 'required',
            'business_no' => 'required',
            'order_type' => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }
        try {
            $receiveNo = $this->receive->create($params);
        } catch (\Exception $e) {
            WarehouseWarning::warningWarehouse('[收货单创建]失败',[$params,$e]);
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse(['receive_no'=>$receiveNo]);
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
        LogApi::debug("取消收货单",$params['receive_no']);
        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            DB::beginTransaction();
            $this->receive->cancel($params['receive_no']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[取消收货单]失败',[$params,$e]);
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
        $param = request()->input();
        $userinfo=$param['userinfo'];

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            DB::beginTransaction();
            Receive::receive($params['receive_no'],$userinfo);
            $this->receive->received($params['receive_no']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[签收-收货]失败',[$params,$userinfo,$e]);
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

    /**
     * 确认入库
     */
    public function imeiIn(){
        $rules = [
            'receive_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }
        DB::beginTransaction();
        try {
            $this->receive->imeiIn($params['receive_no']);
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[确认入库]失败',[$params,$e]);
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }
        DB::commit();

        return apiResponse([]);

    }

    /**
     * 订单2.0工具
     *  确认入库
     *
     * $params=[
     *      order_no=>'订单编号'
     * ]
     */
    public function orderImeiIn(){
        $rules = [
            'order_no' => 'required',
        ];
        $params = $this->_dealParams($rules);
        DB::beginTransaction();
        try{
            ImeiRepository::orderImeiIn($params['order_no']);
        } catch (\Exception $e){
            DB::rollBack();
            LogApi::debug("Receive[orderImeiIn]error:",$e->getMessage());
            WarehouseWarning::warningWarehouse('[确认入库-订单2.0工具]失败',[$params,$e]);
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }
        DB::commit();
        return \apiResponse([], ApiStatus::CODE_0);
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
            'receive_no'  => 'required',
            'logistics_id' => 'required',//物流渠道
            'logistics_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->receive->logistics($params);
            LogApi::debug("退货修改物流信息成功，对应参数",$params);
        } catch (\Exception $e) {
            LogApi::debug("退货修改物流信息失败",$e->getMessage());
            return \apiResponse([], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([], ApiStatus::CODE_0);
    }


    /**
     * 收货单明细收货(暂时弃用)
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
            'goods_no'  => 'required',
            'quantity'   => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            DB::beginTransaction();
            $this->receive->receiveDetail($params);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
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
     * 检测完成
     */
    public function checkItemsFinish()
    {

        $rules = [
            'receive_no' => 'required',
            'check_description' => 'required',
            'check_result' => 'required',
            'compensate_amount' => 'required',
            'goods_no' => 'required'
        ];

        $params = $this->_dealParams($rules);
        LogApi::info("[checkItemsFinish_1]检测完成接收参数",$params);
        $param = request()->input();
        $userinfo=$param['userinfo'];

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            DB::beginTransaction();
            $params['create_time'] = time();

            //$items = $this->receive->checkItemsFinish($params['receive_no']);
            $receive_row = \App\Warehouse\Models\Receive::find($params['receive_no'])->toArray();
            $params['order_no'] = $receive_row['order_no'];
            //$receive_goods = ReceiveGoods::where(['receive_no','=',$params['receive_no']])->first()->toArray();
            $items[] = [
                'goods_no'=>$params['goods_no'],
                'evaluation_status'=>$params['check_result'],
                'evaluation_time'=>$params['create_time'],
                'evaluation_remark'=>$params['check_description'],
                'compensate_amount'=>$params['compensate_amount'],
                'business_no'=>$receive_row['business_no'],
                //'refund_no'=>$receive_goods['receive_no']?$receive_goods['receive_no']:'',
            ];

            LogApi::info('checkItemsFinish_info_Receive',$items);

            $this->receive->checkItem($params);
            Receive::checkItemsResult($items,$receive_row['business_key'],$userinfo);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            LogApi::info('checkItemsFinish_info_Receive_error',$e->getMessage());
            WarehouseWarning::warningWarehouse('[检测完成]失败',[$params,$e]);
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }
        return apiResponse();
    }

    /**
     * 验收 针对设备
     */
    public function check()
    {

        $rules = [
            'receive_no' => 'required',
            'goods_no'  => 'required',
            'imei'       => 'required',
            'check_result'  => 'required', //针对设备的检测结果
        ];

        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->receive->check($params['receive_no'],$params['goods_no'], $params);
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
            'goods_no'  => 'required' //设备序号
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

            $goods = $receive->goods;
            $goods_info = [];

            foreach ($goods as $g) {
                $goods_info[] = [
                    'goods_no' => $g->goods_no,
                    'refund_no' => $g->refund_no,
                    'check_result' => $g->check_result == 1 ? 'success' : 'false',
                    'check_description' => $g->check_description,
                    'evaluation_time' => $g->check_time,
                    'price' => $g->check_price
                ];
            }

            \App\Lib\Order\Receive::checkResult($receive->order_no, $receive->type,$goods_info);

        } catch (\Exception $e) {
            WarehouseWarning::warningWarehouse('[完成签收针对收货单-收货]失败',[$params,$e]);
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
            'goods_no'  => 'required',
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

    /**
     * 确认同意换货
     *      创建发货单
     *
     * 1.验证是否全部合格
     * 2.创建发货单
     */
    public function createDelivery(){
        $rules = [
            'receive_no' => 'required',
            'exchange_description' => 'required'
        ];
        $params = $this->_dealParams($rules);
        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            DB::beginTransaction();
            $this->receive->createDelivery($params);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[确认同意换货]失败',[$params,$e]);
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }
        return \apiResponse([]);
    }


    /**
     * 共用的状态等统一接口
     */
    public function publics()
    {
        $data = [
            'status_list' => \App\Warehouse\Models\Receive::status(),
            'kw_types'    => DeliveryService::searchKws()
        ];
        return apiResponse($data);
    }

    /**
     * 线下门店检测完成
     *
     * @param order_no 订单号
     * @param check_description 检测备注
     * @param check_result 检测结果
     * @param compensate_amount 检测赔偿价格
     * @param amount 押金之外需要赔偿金额
     * @param goods_no 商品编号
     * @param dingsun_type 定损类型
     */
    public function xianxiaCheckItemsFinish()
    {
        $rules = [
            'order_no' => 'required',
            'check_description' => 'required',
            'check_result' => 'required',
            'compensate_amount' => 'required',
            'amount' => 'required',
            'goods_no' => 'required',
            'dingsun_type' => 'required',
        ];

        $params = $this->_dealParams($rules);
        $param = request()->input();
        $userinfo=$param['userinfo'];

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            DB::beginTransaction();
            $params['create_time'] = time();

            //$items = $this->receive->checkItemsFinish($params['receive_no']);
            //$receive_row = \App\Warehouse\Models\Receive::find($params['receive_no'])->toArray();
            $receive_obj = \App\Warehouse\Models\Receive::where(['order_no'=>$params['order_no']])->orderBy('receive_no','desc')->first();
            if(!$receive_obj){
                return apiResponse([], ApiStatus::CODE_60002, '检测单未查到_'.$params['order_no']);
            }
            $receive_row = $receive_obj->toArray();
            $params['receive_no'] = $receive_row['receive_no'];
            //$receive_goods = ReceiveGoods::where(['receive_no','=',$params['receive_no']])->first()->toArray();
            $items[] = [
                'goods_no'=>$params['goods_no'],
                'evaluation_status'=>$params['check_result'],
                'evaluation_time'=>$params['create_time'],
                'evaluation_remark'=>$params['check_description'],
                'compensate_amount'=>$params['compensate_amount'],
                'amount'=>$params['amount'],
                'business_no'=>$receive_row['business_no'],
                //'refund_no'=>$receive_goods['receive_no']?$receive_goods['receive_no']:'',
            ];

            LogApi::info('xianxiaCheckItemsFinish_info_Receive',$params);

            $this->receive->xianxiacheckItem($params);
            Receive::checkItemsResult($items,$receive_row['business_key'],$userinfo);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            LogApi::info('xianxiaCheckItemsFinish_info_Receive_error',$e->getMessage());
            WarehouseWarning::warningWarehouse('[线下门店检测完成]失败',[$params,$e]);
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }
        return apiResponse();
    }

    /**
     * 线下签收
     */
    public function xianxiaReceived()
    {
        $rules = [
            'order_no' => 'required'
        ];
        $params = $this->_dealParams($rules);
        $param = request()->input();
        $userinfo=$param['userinfo'];

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            DB::beginTransaction();
            //向下兼容 receive_no
            $receive_obj = \App\Warehouse\Models\Receive::where(['order_no'=>$params['order_no'],'type'=>\App\Warehouse\Models\Receive::TYPE_BACK,'status'=>\App\Warehouse\Models\Receive::STATUS_INIT,'order_type'=>'2'])->select('receive_no')->orderByDesc('receive_no')->first();
            if(!$receive_obj){
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_40006, '待签收订单未找到:'.$params['order_no']);
            }
            $receive_row = $receive_obj->toArray();
            $params['receive_no'] = $receive_row['receive_no'];
            //记录日志
            LogApi::info('xianxiaReceived_info_Receive',$params);
            //通知订单
            Receive::receive($params['receive_no'],$userinfo);
            //修改状态
            $this->receive->received($params['receive_no']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[签收-收货]失败',[$params,$userinfo,$e]);
            return apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return apiResponse([]);
    }

}

