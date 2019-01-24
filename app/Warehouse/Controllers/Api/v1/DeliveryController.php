<?php

namespace App\Warehouse\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Warehouse\Models\Delivery;
use App\Warehouse\Modules\Repository\DeliveryRepository;
use App\Warehouse\Modules\Service\DeliveryImeiService;
use App\Warehouse\Modules\Service\DeliveryCreater;
use App\Warehouse\Modules\Service\DeliveryService;
use App\Warehouse\Modules\Service\ReceiveGoodsService;
use App\Warehouse\Modules\Service\ReceiveService;
use App\Warehouse\Modules\Service\WarehouseWarning;
use Illuminate\Support\Facades\DB;
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
     * @param
	 * [
	 *		'business_key'	=> '',	//【必选】string 业务类型
	 *		'business_no'	=> '',	//【必选】string 业务编号
	 *		'order_no'		=> '',	//【必选】string 订单编号
	 *		'delivery_detail'	=> [	//【必选】array 发货商品清单
	 *			[
	 *				'goods_name'=> '', //【必选】string 商品名称
	 *				'goods_no'	=> '', //【必选】string 商品编号
	 *				'quantity'	=> '', //【必选】int 申请发货数量
	 *			]
	 *		],
	 *		'customer'			=> '',	//【必选】string 收货人姓名
	 *		'customer_mobile'	=> '',	//【必选】string 收货人手机号
	 *		'customer_address'	=> '',	//【必选】string 收货人地址
     *      'channel_id'=>'',//【必须】string 所属渠道ID
     *      'appid'=>'',//【必须】string 来源appid
	 * ]
     */
    public function deliveryCreate(){
		\App\Lib\Common\LogApi::debug('[发货申请]',request()->input());
		
        $rules = [
            'order_no'			=> 'required', //单号
            'delivery_detail'   => 'required', //序号
            'customer'			=> 'required', //收货人姓名
            'customer_mobile'   => 'required', //手机号
            'customer_address'  => 'required', //地址
            'business_key'		=> 'required', //业务类型
            'business_no'		=> 'required', //业务编号
            'predict_delivery_time'=>'required', //预计发货时间
            'channel_id'=>'required', //预计发货时间
            'appid'=>'required', //预计发货时间
            'order_type'=>'required'//订单类型
        ];

        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $this->DeliveryCreate->confirmation($params);
        } catch (\Exception $e) {
			\App\Lib\Common\LogApi::type('error')::error('[发货申请]失败', $e);
            WarehouseWarning::warningWarehouse('[发货申请]失败',[$params,$e]);
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }
		\App\Lib\Common\LogApi::debug('[发货申请]成功');
        return \apiResponse([]);
    }


    /**
     * 单件配货
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function matchGoods()
    {
        $rules = [
            'delivery_no' => 'required', //单号
            'goods_no'   => 'required', //序号
            'imei'    => 'required', //imei
            'price'    => 'required', //采购价
            'apple_serial'    => 'required', //苹果手机序列号
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
            WarehouseWarning::warningWarehouse('[配货完成]失败',[$params,$e]);
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
            WarehouseWarning::warningWarehouse('[取消发货,订单系统过来的请求]失败',[$params,$e]);
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 取消发货后,退货退款审核未通过,继续发货
     *
     * order_no 订单号
     * status 4=待发货,5已发货待签收
     */
    public function auditFailed()
    {
        $rules = [
            'order_no'  => 'required',
            'status'    => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }
        DB::beginTransaction();
        try {
            $this->delivery->auditFailed($params);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[取消发货后,退货退款审核未通过,继续发货]失败',[$params,$e]);
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
            WarehouseWarning::warningWarehouse('[客服取消发货]失败',[$params,$e]);
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
            'order_no' => 'required',
            'receive_type'=> 'required',
            'user_id'=> 'required',
            'user_name'=> 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        $receive_type = isset($params['receive_type']) ? $params['receive_type'] : Delivery::RECEIVE_TYPE_USER;

        try {
            $deliveryInfo = $this->delivery->receive($params['order_no'], $receive_type);

            //\App\Lib\Warehouse\Delivery::receive($deliveryInfo['order_no'], ['receive_type'=>$params['receive_type'],'user_id'=>$params['user_id'],'user_name'=>$params['user_name']]);

        } catch (\Exception $e) {
            WarehouseWarning::warningWarehouse('[签收-发货]失败',[$params,$e]);
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }

        return \apiResponse([],ApiStatus::CODE_0);
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
        //回寄地址
        $result['return_address_info']=Delivery::RETURN_ADDRESS;

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
            'return_address_type'=> 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }


        try {
            DB::beginTransaction();
            $result = $this->_info($params['delivery_no']);

            $orderDetail = [
                'order_no' => $result['order_no'],
                'logistics_id' => $params['logistics_id'],
                'logistics_no' => $params['logistics_no'],
                'logistics_note'=>$params['logistics_note'],
                'return_address_type'=>false
            ];
            if($params['return_address_type']){
                $orderDetail['return_address_type']=true;
                $orderDetail['return_address_value']=Delivery::RETURN_ADDRESS[$params['return_address_type']]['return_address_value'];
                $orderDetail['return_name']=Delivery::RETURN_ADDRESS[$params['return_address_type']]['return_name'];
                $orderDetail['return_phone']=Delivery::RETURN_ADDRESS[$params['return_address_type']]['return_phone'];

            }

            LogApi::info('delivery_send_info:',$params);

            //操作员信息,用户或管理员操作有
            $user_info['user_id'] = $params['user_id'];
            $user_info['user_name'] = $params['user_name'];
            $user_info['type'] = $params['type'];

            //修改发货信息
            $this->delivery->send($params);
            //通知订单接口
            $a = \App\Lib\Warehouse\Delivery::delivery($orderDetail, $result['goods_info'], $user_info);
            LogApi::info('delivery_send_order_info:',$result['order_no'].'_'.$a);
            if(!$a){
                DB::rollBack();
                WarehouseWarning::warningWarehouse('[发货]通知订单失败',[$params,$a]);
                return \apiResponse([$result['order_no'].'_'.$a], ApiStatus::CODE_50001, session()->get(\App\Lib\Warehouse\Delivery::SESSION_ERR_KEY));

            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[发货]失败',[$params,$e]);
            return \apiResponse([$result['order_no'].'_'.$a], ApiStatus::CODE_50000, $e->getMessage());
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
     * 取消商品配货 完成后 为待配货状态
	 * @param 
	 * [
	 *		'delivery_no'	=> '', //【必选】string 发货单
	 *		'goods_no'		=> '', //【必选】string 商品编号
	 * ]
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
            'order_no' => 'required',
            'imei'  => 'required'
        ];
        $params = $this->_dealParams($rules);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $server->del($params['delivery_no'], $params['imei'], $params['order_no']);
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
            'order_no' => 'required',
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
    public function lists()
    {
        $params = $this->_dealParams([]);
        $request = request()->input();
        $params['channel_id'] = json_decode($request['userinfo']['channel_id'], true);
        $list = $this->delivery->lists($params);
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
        //return apiResponse(['list'=>$list]);
        return apiResponse($list);
    }

    /**
     * 根据订单获取发货单状态是否属于已配货
     */
    public function getStatus(){
        $rules = [
            'order_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

        if (!$params['order_no']) {
            return \apiResponse([], ApiStatus::CODE_10104, 'order_no必须');
        }

        $status = DeliveryRepository::getStatus($params['order_no']);
        if($status){
            return \apiResponse(['status'=>$status]);
        } else{
            return \apiResponse([], ApiStatus::CODE_10104, 'order_no未查到');
        }

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

    /**
     * 渠道商发货
     */
    public function channelSend(){
        $rules = [
            'delivery_no' => 'required',//发货单号必填
            'imei' => 'required',//IMEI必填
            'order_no' => 'required',//订单编号必填
            'goods_no' => 'required', //商品编号
            'logistics_id' => 'required', //物流ID
            'logistics_no' => 'required', //物流编号
            'apple_serial' => 'required',//设备序列号选填
            'price' => 'required',//采购价选填
        ];
        $params = $this->_dealParams($rules);

        if (count($params)<6) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }
        LogApi::info('delivery_send_info_channelSend',$params);

        $request = request()->input();
        $user = $request['userinfo'];

        try {
            DB::beginTransaction();
            $result = $this->_info($params['delivery_no']);
            $orderDetail = [
                'order_no' => $result['order_no'],
                'logistics_id' => $params['logistics_id']??1,
                'logistics_no' => $params['logistics_no']??'0',
                'logistics_note'=>'0'
            ];

            //操作员信息,用户或管理员操作有
            $user_info['user_id'] = $user['uid'];
            $user_info['user_name'] = $user['username'];
            $user_info['type'] = $user['type'];
            //通知订单接口
            $result['goods_info'][$params['goods_no']]['imei1']=$params['imei'];
            $result['goods_info'][$params['goods_no']]['serial_number']=$params['apple_serial']??'';
            //修改发货信息
            DeliveryService::channelSend($params);
            LogApi::info('delivery_send_order_info_channelSend',[$orderDetail,$result['goods_info'],$user_info]);
            $a = \App\Lib\Warehouse\Delivery::delivery($orderDetail, $result['goods_info'], $user_info);
            if(!$a){
                DB::rollBack();
                return \apiResponse([$params['delivery_no'].'_'.$a], ApiStatus::CODE_50001, session()->get(\App\Lib\Warehouse\Delivery::SESSION_ERR_KEY));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[渠道商发货]失败',[$params,$e]);
            return \apiResponse([$params['delivery_no'].'_'.$a], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 线下门店待发货数量
     */
    public function deliveryNum(){
        $request = request()->input();
        $channel_id = json_decode($request['userinfo']['channel_id'], true);
        $count = Delivery::where(['status'=>Delivery::STATUS_INIT,'channel_id'=>$channel_id])->count();
        return \apiResponse(['count'=>$count]);
    }

    /**
     * 线下门店待发货列表
     */
    public function xianxiaList(){
        $request = request()->input();
        $channel_id = json_decode($request['userinfo']['channel_id'], true);
        LogApi::info('delivery_xianxiaList',[$channel_id]);
        $list_obj = Delivery::where(['status'=>Delivery::STATUS_INIT,'channel_id'=>$channel_id])->get();
        if($list_obj){
            $list = [];
            foreach ($list_obj as $key=>$item){
                $list[$key] = $item->toArray();
                $list[$key]['goods_list'] = $item->goods->toArray();
                foreach ($list[$key]['goods_list'] as $k=>$val){
                    $list[$key]['goods_list'][$k]['specs'] = empty($val['specs'])?'':filterSpecs($val['specs']);
                }
            }
            return \apiResponse($list);
        }else{
            return \apiResponse([]);
        }
    }

    /**
     * 线下门店发货
     *
     * @param IMEI
     * @param 发货备注
     * @param 图片
     */
    public function xianxiaDelivery(){
        $rules = [
            'delivery_no' => 'required',//发货单号必填
            'imei' => 'required',//IMEI必填
            'imei_id' => 'required',//必填
            'order_no' => 'required',//订单编号必填
            'goods_no' => 'required', //商品编号
            'apple_serial' => 'required', //针对苹果的序列号
            'price' => 'required', //采购价格
            'logistics_note' => 'required', //发货备注
        ];
        $params = $this->_dealParams($rules);

        if (count($params)<5) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }
        LogApi::info('delivery_send_info_channelSend',$params);

        $request = request()->input();
        $user = $request['userinfo'];

        //设置默认数据
        $params['logistics_id']=1001;
        $params['logistics_no']='1001';
        //$params['logistics_note']='线下发货';

        try {
            DB::beginTransaction();
            //修改发货信息
            DeliveryService::xianxiaSend($params);
            //通知订单接口
            $result = [
                'order_no'=>$params['order_no'],
                'goods_info'=>[
                    $params['goods_no']=>['goods_no'=>$params['goods_no'],'imei1'=>$params['imei'],'serial_number'=>$params['apple_serial']]
                ]
            ];
            //线下发货
            $orderDetail = [
                'order_no' => $result['order_no'],
                'logistics_id' => $params['logistics_id'],
                'logistics_no' => $params['logistics_no'],
                'logistics_note'=>$params['logistics_note']
            ];

            //操作员信息,用户或管理员操作有
            $user_info['user_id'] = $user['uid'];
            $user_info['user_name'] = $user['username'];
            $user_info['type'] = $user['type'];

            LogApi::info('delivery_send_order_info_xianxiaDelivery',[$orderDetail,$result['goods_info'],$user_info]);
            $a = \App\Lib\Warehouse\Delivery::delivery($orderDetail, $result['goods_info'], $user_info);
            if(!$a){
                DB::rollBack();
                WarehouseWarning::warningWarehouse('[线下门店发货]通知订单失败',[$params,$a]);
                return \apiResponse([$result['order_no']], ApiStatus::CODE_50001, session()->get(\App\Lib\Warehouse\Delivery::SESSION_ERR_KEY));

            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            WarehouseWarning::warningWarehouse('[线下门店发货]失败',[$params,$e]);
            return \apiResponse([$result['order_no']], ApiStatus::CODE_50000, $e->getMessage());
        }

        return \apiResponse([]);
    }

    /**
     * 线下发货清单
     */
    public function xianxiaShow()
    {
        $rules = [
            'order_no' => 'required',
        ];
        $params = $this->_dealParams($rules);

//        $request = request()->input();
//        $params['channel_id'] = json_decode($request['userinfo']['channel_id'], true);

        if (!$params) {
            return \apiResponse([], ApiStatus::CODE_10104, session()->get(self::SESSION_ERR_KEY));
        }

        try {
            $result = $this->delivery->detail($params['order_no']);
        } catch (\Exception $e) {
            return \apiResponse([], ApiStatus::CODE_60002, $e->getMessage());
        }
        //回寄地址
        $result['return_address_info']=Delivery::RETURN_ADDRESS;

        return \apiResponse($result);
    }


    /**
     * @param $delivery_no
     * @return array
     */
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

