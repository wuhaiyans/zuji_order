<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\PublicFunc;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderBuyout;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderInstalmentRepository;
use Illuminate\Support\Facades\DB;

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
        $this->OrderGoodsRepository = new OrderGoodsRepository;
        $goodsInfo = $this->OrderGoodsRepository->getGoodsInfo($buyoutInfo['goods_no']);
        if(empty($goodsInfo)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到相关数据");
        }
        $goodsInfo['status'] = $buyoutInfo['status'];
        $goodsInfo['buyout_price'] = $buyoutInfo['buyout_price'];
        return apiResponse($goodsInfo,ApiStatus::CODE_0);
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
                $where['user_mobile'] = $params['keywords'];
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
        $sumCount = OrderBuyout::getCount($where);
        $where['page'] = $params['page']>0?$params['page']-1:0;
        $where['size'] = $params['size']?$params['size']:config('web.pre_page_size');
        $orderList = OrderBuyout::getList($where);
        return apiResponse($orderList,ApiStatus::CODE_0);
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

        if (empty($params['goods_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"goods_no必须");
        }
        if (empty($params['user_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"user_id必须");
        }
        //获取订单商品信息
        $this->OrderGoodsRepository = new OrderGoodsRepository;
        $goodsInfo = $this->OrderGoodsRepository->getGoodsInfo($params['goods_no']);
        if(empty($goodsInfo)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        //验证商品是否冻结
        if($goodsInfo['goods_status']==OrderGoodStatus::BUY_OFF){
            return apiResponse([],ApiStatus::CODE_20001,"该订单商品正买断进行中");
        }
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $orderInfo = $this->OrderRepository->get_order_info(['order_no'=>$goodsInfo['order_no'],"user_id"=>$params['user_id']]);
        //验证商品是否冻结
        if($orderInfo['freeze_type']>0){
            return apiResponse([],ApiStatus::CODE_20001,"该订单当前状态不能买断");
        }

        //获取订单商品服务时间
        $this->OrderGoodsUnitRepository = new OrderGoodsUnitRepository;
        $goodsServe = $this->OrderGoodsUnitRepository->getGoodsUnitInfo($params['goods_no']);
        //按天处理
        if($goodsServe['unit'] == 1){
            $triggerTime = config("web.day_expiry_process_days");
        }
        //按月处理
        elseif($goodsServe['unit'] == 2){
            $triggerTime = config("web.month_expiry_process_days");
        }
        $newTime = $goodsServe['end_time']-$triggerTime;
        if($newTime>time()){
            return apiResponse([],ApiStatus::CODE_20001,"该订单未到买断时间");
        }
        DB::beginTransaction();
        //创建买断单
        $data = [
            'buyout_no'=>createNo(8),
            'order_no'=>$goodsInfo['order_no'],
            'goods_no'=>$goodsInfo['goods_no'],
            'user_id'=>$goodsInfo['user_id'],
            'goods_name'=>$goodsInfo['goods_name'],
            'buyout_price'=>$goodsInfo['buyout_price'],
            'create_time'=>time(),
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        $goods = [
            'goods_status' => OrderGoodStatus::BUY_OFF,
            'business_no' => $data['buyout_no'],
        ];
        $ret = $this->OrderGoodsRepository->update(['id'=>$goodsInfo['id']],$goods);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
        DB::commit();
        return apiResponse($goodsInfo,ApiStatus::CODE_0);
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
        $orders =$request->all();
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

        //获取订单商品信息
        $this->OrderGoodsRepository = new OrderGoodsRepository;
        $goodsInfo = $this->OrderGoodsRepository->getGoodsInfo($params['goods_no']);
        if(empty($goodsInfo)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
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
        $instaulment = OrderInstalmentRepository::getSumAmount($where);
        $fenqiAmount = $instaulment?$instaulment:0;

        DB::beginTransaction();
        //创建买断单
        $data = [
            'buyout_no'=>createNo(8),
            'order_no'=>$goodsInfo['order_no'],
            'goods_no'=>$goodsInfo['goods_no'],
            'user_id'=>$goodsInfo['user_id'],
            'plat_id'=>$params['user_id'],
            'buyout_price'=>$params['buyout_price']?$params['buyout_price']+$fenqiAmount:$goodsInfo['buyout_price']+$fenqiAmount,
            'create_time'=>time()
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        $goods = [
            'goods_status' => OrderGoodStatus::BUY_OFF,
            'business_no' => $data['buyout_no'],
        ];
        $ret = $this->OrderGoodsRepository->update(['id'=>$goodsInfo['id']],$goods);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            DB::rollBack();
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
        DB::commit();
        return apiResponse($goodsInfo,ApiStatus::CODE_0);
    }
    /*
     * 取消买断
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "goods_no"=>"",商品编号
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
        DB::commit();
        return apiResponse($goodsInfo,ApiStatus::CODE_0);
    }
    /*
     * 买断支付请求
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "goods_no"=>"",商品编号
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
            'callback_url'=>'required'
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        }
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
            'businessNo' => $buyout['buyout_no'],
            'paymentAmount' => $buyout['buyout_price'],
            'paymentFenqi' => 0,
        ];
        \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payInfo);

        $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_BUYOUT, $buyout['buyout_no']);

        $paymentUrl = $pay->getCurrentUrl($params['channel_id'], [
            'name'=>'订单' .$buyout['order_no']. '设备'.$buyout['goods_no'].'买断支付',
            'front_url' => $params['callback_url'],
        ]);
        return apiResponse($paymentUrl,ApiStatus::CODE_0);
    }

}
