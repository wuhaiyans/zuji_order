<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\JobQueueApi;
use App\Lib\PublicFunc;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Repository\Order\Goods;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderBuyout;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use Illuminate\Support\Facades\DB;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\GoodsLogRepository;

/**
 * 订单买断接口控制器
 * @var obj BuyoutController
 * @author limin<limin@huishoubao.com.cn>
 */

class BuyoutController extends Controller
{
    /*
     * 买断详情
     * @param array $params 【必选】
     * [
     *      "id"=>"",买断单id
     * ]
     * @return json
     */
    public function getBuyout(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        //过滤参数
        $params= filter_array($params,[
            'buyout_no'=>'required',
        ]);
        $buyoutInfo = OrderBuyout::getInfo($params['buyout_no']);
        if(empty($buyoutInfo)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到相关数据");
        }
        $goodsInfo = Goods::getByGoodsNo($buyoutInfo['goods_no']);
        if(empty($goodsInfo)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到相关数据");
        }
        $goodsInfo = $goodsInfo->getData();
        $goodsInfo['specs'] = filterSpecs($goodsInfo['specs']);
        $goodsInfo['status'] = $buyoutInfo['status'];
        $goodsInfo['buyout_price'] = $buyoutInfo['buyout_price'];
        $goodsInfo['zujin_price'] = $buyoutInfo['zujin_price'];
        $goodsInfo['zuqi_number'] = $buyoutInfo['zuqi_number'];
        $goodsInfo['amount'] = $buyoutInfo['amount'];
        return apiResponse($goodsInfo,ApiStatus::CODE_0);
    }
    /*
     * 订单买断列表筛选条件
     * @param array $params 【null】
     * @return json
     */
    public function getCondition(){
        $data['status'] = [
            '0'=>"待支付",
            '1'=>"已取消",
            '2'=>"已支付",
            '3'=>"已解押",
        ];
        $data['keywords'] = [
            '1'=>"订单号",
            '2'=>"商品名称",
            '3'=>"手机号",
        ];
        return apiResponse($data,ApiStatus::CODE_0);
    }
    /*
     * 订单买断列表
     * @param array $params 【必选】
     * [
     *      "id"=>"",买断单id
     * ]
     * @return json
     */
    public function getBuyoutList(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        $where = [];
        if(isset($params['keywords'])){
            if($params['kw_type'] == 1){
                $where['order_no'] = $params['keywords'];
            }
            elseif($params['kw_type'] == 2){
                $where['goods_name'] = $params['keywords'];
            }
            elseif($params['kw_type'] == 3){
                $where['mobile'] = $params['keywords'];
            }
            else{
                $where['order_no'] = $params['keywords'];
            }
        }
        if(isset($params['begin_time'])||isset($params['end_time'])){
            $where['begin_time'] = $params['begin_time'];
            $where['end_time'] = $params['end_time'];
        }
        if(isset($params['status'])){
            $where['status'] = $params['status'];
        }
        if(isset($params['appid'])){
            $where['appid'] = $params['appid'];
        }
        //$sumCount = OrderBuyout::getCount($where);
        $where['page'] = $params['page']>0?$params['page']-1:0;
        $where['size'] = $params['size']?$params['size']:config('web.pre_page_size');
        $orderList = OrderBuyout::getList($where);

        if(!$orderList){
            return apiResponse([],ApiStatus::CODE_0);
        }
        //获取订单商品信息
        $goodsNos = array_column($orderList['data'],"goods_no");
        $goodsList= OrderGoodsRepository::getGoodsColumn($goodsNos);
        //获取订单用户信息
        $orderNos = array_column($orderList['data'],"order_no");
        $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);

        foreach($orderList['data'] as &$item){
            $item['status'] = OrderBuyoutStatus::getStatusName($item['status']);
            $item['realname'] = $userList[$item['order_no']]['realname'];
            $item['yajin'] = $goodsList[$item['goods_no']]['yajin'];
            $item['zuqi'] = $goodsList[$item['goods_no']]['zuqi'];
            $item['zuqi_type']= OrderStatus::getZuqiTypeName($goodsList[$item['goods_no']]['zuqi_type']);
            $item['order_time'] = date("Y-m-d H:i:s",$item['order_time']);
        }

        return apiResponse($orderList,ApiStatus::CODE_0);
    }
    /**
     * 买断单列表导出接口
     * Author: heaven
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function listExport(Request $request) {

        $params =$request->all();

        $where = [];
        if(isset($params['keywords'])){
            if($params['kw_type'] == 1){
                $where['order_no'] = $params['keywords'];
            }
            elseif($params['kw_type'] == 2){
                $where['goods_name'] = $params['keywords'];
            }
            elseif($params['kw_type'] == 3){
                $where['mobile'] = $params['keywords'];
            }
            else{
                $where['order_no'] = $params['keywords'];
            }
        }
        if(isset($params['begin_time'])||isset($params['end_time'])){
            $where['begin_time'] = $params['begin_time'];
            $where['end_time'] = $params['end_time'];
        }
        if(isset($params['status'])){
            $where['status'] = $params['status'];
        }
        if(isset($params['appid'])){
            $where['appid'] = $params['appid'];
        }
        //$sumCount = OrderBuyout::getCount($where);
        $where['page'] = $params['page']>0?$params['page']-1:0;
        $where['size'] = 10000;

        $orderList = OrderBuyout::getList($where);

        if(!$orderList){
            return apiResponse([],ApiStatus::CODE_0);
        }
        //获取订单商品信息
        $goodsNos = array_column($orderList['data'],"goods_no");
        $goodsList= OrderGoodsRepository::getGoodsColumn($goodsNos);
        //获取订单用户信息
        $orderNos = array_column($orderList['data'],"order_no");
        $userList = OrderUserCertifiedRepository::getUserColumn($orderNos);

        //定义excel头部参数名称
        $headers = [
            '订单编号',
            '下单时间',
            '交易流水号',
            '用户名',
            '手机号',
            '设备名称',
            '订单金额',
            '租期',
            '买断设备',
            '买断金额',
            '应退押金',
            '状态',
        ];
        foreach($orderList['data'] as &$item){
            $item['status'] = OrderBuyoutStatus::getStatusName($item['status']);
            $item['realname'] = $userList[$item['order_no']]['realname'];
            $item['yajin'] = $goodsList[$item['goods_no']]['yajin'];
            $item['zuqi'] = $goodsList[$item['goods_no']]['zuqi'];
            $item['zuqi_type']= OrderStatus::getZuqiTypeName($goodsList[$item['goods_no']]['zuqi_type']);
            $item['order_time'] = date("Y-m-d H:i:s",$item['order_time']);

            $data[] = [
                $item['order_no'],
                $item['create_time'],
                $item['buyout_no'],
                $item['realname'],
                $item['mobile'],
                $item['goods_name'],
                $item['order_amount'],
                $item['zuqi'],
                $item['goods_name'],
                $item['amount'],
                $item['yajin'],
                $item['status'],
            ];
        }

        return \App\Lib\Excel::write($data, $headers,'后台买断单列表数据导出-');


    }
    /*
     * 用户买断
     * @param array $params 【必选】
     * [
     *      "goods_no"=>"",商品编号
     *      "user_id"=>"", 用户id
     * ]
     * @return json
     */
    public function userBuyout(Request $request)
    {
        //接收请求参数
        $orders =$request->all();
        $params = $orders['params'];
        //过滤参数
        $rule= [
            'goods_no'=>'required',
            'user_id'=>'required',
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        }
        $userInfo = $orders['userinfo'];
        if (empty($params['goods_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"goods_no必须");
        }
        if (empty($params['user_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"user_id必须");
        }
        //获取订单商品信息
        $goodsObj = Goods::getByGoodsNo($params['goods_no']);
        if(empty($goodsObj)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        $goodsInfo = $goodsObj->getData();
        //验证商品是否冻结
        if($goodsInfo['goods_status']==OrderGoodStatus::BUY_OFF){
            return apiResponse([],ApiStatus::CODE_20001,"该订单商品正买断进行中");
        }
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $orderInfo = $this->OrderRepository->getInfoById($goodsInfo['order_no'],$params['user_id']);
        //验证商品是否冻结
        if($orderInfo['freeze_type']>0){
            return apiResponse([],ApiStatus::CODE_20001,"该订单当前状态不能买断");
        }

        //按天处理
        if($goodsInfo['zuqi_type'] == 1){
            $triggerTime = config("web.day_expiry_process_days");
        }
        //按月处理
        elseif($goodsInfo['zuqi_type'] == 2){
            $triggerTime = config("web.month_expiry_process_days");
        }
        $newTime = $goodsInfo['end_time']-$triggerTime;
        if($newTime>time()){
            return apiResponse([],ApiStatus::CODE_20001,"该订单未到买断时间");
        }
        //获取剩余未支付租金
        $where[] = ['status','=', \App\Order\Modules\Inc\OrderInstalmentStatus::UNPAID];
        $where[] = ['goods_no','=',$goodsInfo['goods_no']];
        $instaulment = OrderGoodsInstalmentRepository::getSumAmount($where);
        $fenqiPrice = $instaulment['amount']?$instaulment['amount']:0;
        $fenqishu = $instaulment['fenqishu']?$instaulment['fenqishu']:0;
        $buyoutPrice = $goodsInfo['buyout_price'];

        DB::beginTransaction();
        //创建买断单
        $data = [
            'buyout_no'=>createNo(8),
            'order_no'=>$goodsInfo['order_no'],
            'goods_no'=>$goodsInfo['goods_no'],
            'user_id'=>$goodsInfo['user_id'],
            'goods_name'=>$goodsInfo['goods_name'],
            'buyout_price'=>$buyoutPrice,
            'zujin_price'=>$fenqiPrice,
            'zuqi_number'=>$fenqishu,
            'amount'=>$buyoutPrice+$fenqiPrice,
            'create_time'=>time()
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        //更新订单商品状态
        $ret = $goodsObj->buyoutOpen(['business_no' => $data['buyout_no']]);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
        //发送短信
        $notice = new \App\Order\Modules\Service\OrderNotice(OrderStatus::BUSINESS_GIVEBACK,$data['buyout_no'],"BuyoutConfirm");
        $notice->notify();
        //插入订单日志
        OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$goodsInfo['order_no'],"用户到期买断","创建买断成功");
        //插入订单设备日志
        $log = [
            'order_no'=>$data['order_no'],
            'action'=>'买断生成',
            'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT,//此处用常量
            'business_no'=>$data['buyout_no'],
            'goods_no'=>$data['goods_no'],
            'operator_id'=>$userInfo['uid'],
            'operator_name'=>$userInfo['username'],
            'operator_type'=>$userInfo['type'],
            'msg'=>'用户到期买断',
        ];
        GoodsLogRepository::add($log);
        DB::commit();
        //加入队列执行超时取消订单
        $b =JobQueueApi::addScheduleOnce(config('app.env')."-OrderCancelBuyout_".$data['buyout_no'],config("ordersystem.ORDER_API"), [
            'method' => 'api.buyout.cancel',
            'params' => [
                'buyout_no'=>$data['buyout_no'],
                'user_id'=>$data['user_id'],
            ],
        ],time()+config('web.order_cancel_hours'),"");

        return apiResponse(array_merge($goodsInfo,$data),ApiStatus::CODE_0);
    }

    /*
     * 管理操作买断
     * @param array $params 【必选】
     * [
     *      "goods_no"=>"",商品编号
     *      "user_id"=>"", 操作人id
     *      "buyout_price"=>"",买断金额
     * ]
     * @return json
     */
    public function adminBuyout(Request $request)
    {
        //接收请求参数
        $orders = $request->all();
        $params = $orders['params'];
        //过滤参数
        $rule= [
            'goods_no'=>'required',
            'user_id'=>'required',
            'buyout_price'=>'required',
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        }
        $userInfo = $orders['userinfo'];
        //获取订单商品信息
        $goodsObj = Goods::getByGoodsNo($params['goods_no']);
        if(empty($goodsObj)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        $goodsInfo = $goodsObj->getData();
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $orderInfo = $this->OrderRepository->getInfoById(['order_no'=>$goodsInfo['order_no'],"user_id"=>$goodsInfo['user_id']]);
        if(empty($orderInfo)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        //验证商品是否冻结
        if($orderInfo['freeze_type']>0){
            return apiResponse([],ApiStatus::CODE_20001,"该订单当前状态不能买断");
        }
        //获取剩余未支付租金
        $where[] = ['status','=', \App\Order\Modules\Inc\OrderInstalmentStatus::UNPAID];
        $where[] = ['goods_no','=',$goodsInfo['goods_no']];
        $instaulment = OrderGoodsInstalmentRepository::getSumAmount($where);
        $fenqiPrice = $instaulment['amount']?$instaulment['amount']:0;
        $fenqishu = $instaulment['fenqishu']?$instaulment['fenqishu']:0;
        $buyoutPrice = $params['buyout_price']?$params['buyout_price']:$goodsInfo['buyout_price'];

        DB::beginTransaction();
        //创建买断单
        $data = [
            'type'=>1,
            'buyout_no'=>createNo(8),
            'order_no'=>$goodsInfo['order_no'],
            'goods_no'=>$goodsInfo['goods_no'],
            'user_id'=>$goodsInfo['user_id'],
            'plat_id'=>$params['user_id'],
            'goods_name'=>$goodsInfo['goods_name'],
            'buyout_price'=>$buyoutPrice,
            'zujin_price'=>$fenqiPrice,
            'zuqi_number'=>$fenqishu,
            'amount'=>$buyoutPrice+$fenqiPrice,
            'create_time'=>time()
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        $ret = $goodsObj->buyoutOpen(['business_no' => $data['buyout_no']]);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
        //发送短信
        $notice = new \App\Order\Modules\Service\OrderNotice(OrderStatus::BUSINESS_GIVEBACK,$data['buyout_no'],"BuyoutConfirm");
        $notice->notify();
        //插入订单日志
        OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$goodsInfo['order_no'],"客服操作提前买断","创建买断成功");
        //插入订单设备日志
        $log = [
            'order_no'=>$data['order_no'],
            'action'=>'买断生成',
            'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT,//此处用常量
            'business_no'=>$data['buyout_no'],
            'goods_no'=>$data['goods_no'],
            'operator_id'=>$userInfo['uid'],
            'operator_name'=>$userInfo['username'],
            'operator_type'=>$userInfo['type'],
            'msg'=>'客服提前买断',
        ];
        GoodsLogRepository::add($log);
        DB::commit();

        //加入队列执行超时取消订单
        $b =JobQueueApi::addScheduleOnce(config('app.env')."-OrderCancelBuyout_".$data['buyout_no'],config("ordersystem.ORDER_API")."/CancelOrderBuyout", [
            'buyout_no'=>$data['buyout_no'],
            'user_id'=>$data['user_id'],
        ],time()+config('web.order_cancel_hours'),"");

        return apiResponse(array_merge($goodsInfo,$data),ApiStatus::CODE_0);
    }
    /*
     * 取消买断
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "buyout_no"=>"",买断业务号
     * ]
     * @return json
     */
    public function cancel(Request $request){
        //接收请求参数
        $orders =$request->all();
        $params = $orders['params'];
        //过滤参数
        $rule= [
            'buyout_no'=>'required',
            'user_id'=>'required',
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        }
        $userInfo = $orders['userinfo'];
        //获取买断单
        $buyout = OrderBuyout::getInfo($params['buyout_no']);
        if($buyout['status']!=OrderBuyoutStatus::OrderInitialize){
            return apiResponse([],ApiStatus::CODE_50001,"该订单不能取消买断");
        }
        //获取订单商品信息
        $this->OrderGoodsRepository = new OrderGoodsRepository;
        $goodsInfo = $this->OrderGoodsRepository->getGoodsInfo($buyout['goods_no']);
        if(empty($goodsInfo)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $orderInfo = $this->OrderRepository->get_order_info(['order_no'=>$goodsInfo['order_no'],"user_id"=>$goodsInfo['user_id']]);
        if(empty($orderInfo)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        if($orderInfo['freeze_type']!=OrderFreezeStatus::Buyout){
            return apiResponse([],ApiStatus::CODE_50001,"该订单不在买断状态");
        }
        DB::beginTransaction();
        //解冻订单-执行取消操作
        $ret = $this->OrderRepository->orderFreezeUpdate($orderInfo['order_no'],OrderFreezeStatus::Non);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_50001,"订单解冻失败");
        }
        $ret = OrderBuyout::cancel($buyout['id'],$params['user_id']);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_50001,"取消失败");
        }
        //插入日志
        OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$goodsInfo['order_no'],"客服取消买断","取消成功");
        //插入订单设备日志
        $log = [
            'order_no'=>$buyout['order_no'],
            'action'=>'客服取消买断生成',
            'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT,//此处用常量
            'business_no'=>$buyout['buyout_no'],
            'goods_no'=>$buyout['goods_no'],
            'operator_id'=>$userInfo['uid'],
            'operator_name'=>$userInfo['username'],
            'operator_type'=>$userInfo['type'],
            'msg'=>'取消买断成功',
        ];
        GoodsLogRepository::add($log);

        DB::commit();

        return apiResponse($goodsInfo,ApiStatus::CODE_0);
    }
    /*
     * 买断支付请求
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "goods_no"=>"",商品编号
     *      "callback_url"=>"",前端回跳地址
     * ]
     * @return json
     */
    public function pay(Request $request){
        //接收请求参数
        $orders =$request->all();
        $params = $orders['params'];
        $params['channel_id'] = 2;
        //过滤参数
        $rule= [
            'buyout_no'=>'required',
            'user_id'=>'required',
            'callback_url'=>'required',
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        }
        $userInfo = $orders['userinfo'];
        //获取买断单
        $buyout = OrderBuyout::getInfo($params['buyout_no'],$params['user_id']);
        if(!$buyout){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        if($buyout['status']==OrderBuyoutStatus::OrderPaid){
            return apiResponse([],ApiStatus::CODE_0,"该订单已支付");
        }
        if($buyout['status']!=OrderBuyoutStatus::OrderInitialize){
            return apiResponse([],ApiStatus::CODE_0,"该订单支付异常");
        }

        $payInfo = [
            'businessType' => ''.OrderStatus::BUSINESS_BUYOUT,
            'userId' => $buyout['user_id'],
            'orderNo' => $buyout['order_no'],
            'businessNo' => $buyout['buyout_no'],
            'paymentAmount' => $buyout['amount'],
            'paymentFenqi' => 0,
        ];
        \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payInfo);

        $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_BUYOUT, $buyout['buyout_no']);

        $paymentUrl = $pay->getCurrentUrl($params['channel_id'], [
            'name'=>'订单' .$buyout['order_no']. '设备'.$buyout['goods_no'].'买断支付',
            'front_url' => $params['callback_url'],
        ]);
        //插入日志
        OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$buyout['order_no'],"用户买断发起支付","创建支付成功");
        //插入订单设备日志
        $log = [
            'order_no'=>$buyout['order_no'],
            'action'=>'用户买断支付',
            'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT,//此处用常量
            'business_no'=>$buyout['buyout_no'],
            'goods_no'=>$buyout['goods_no'],
            'operator_id'=>$userInfo['uid'],
            'operator_name'=>$userInfo['username'],
            'operator_type'=>$userInfo['type'],
            'msg'=>'用户发起支付',
        ];
        GoodsLogRepository::add($log);

        return apiResponse($paymentUrl,ApiStatus::CODE_0);
    }

}
