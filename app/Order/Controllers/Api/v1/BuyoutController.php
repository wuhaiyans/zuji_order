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
            'goods_no'=>'required',
        ]);
        $buyoutInfo = OrderBuyout::getInfo($params['goods_no']);
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
        //过滤参数
        $params = filter_array($params, [
            'type'  => 'required',
            'word'  => 'required',
            'status'  => 'required',
            'appid'  => 'required',
            'begin_time' => 'required',
            'end_time' => 'required',
            'offset'    => 'required',
            'limit'     => 'required',
        ]);
        $where = [];
        if($params['word']){
            if($params['type'] == 1){
                $where['order_no'] = $params['word'];
            }
            elseif($params['type'] == 2){
                $where['goods_name'] = $params['word'];
            }
            elseif($params['type'] == 3){
                $where['user_mobile'] = $params['word'];
            }
            else{
                $where['order_no'] = $params['word'];
            }
        }
        if($params['begin_time']||$params['end_time']){
            $where['begin_time'] = $params['begin_time'];
            $where['end_time'] = $params['end_time'];
        }
        if($params['status']){
            $where['status'] = $params['status'];
        }
        if($params['appid']){
            $where['appid'] = $params['appid'];
        }
        $sumCount = OrderBuyout::getCount($where);
        $where['offset'] = $params['offset']?$params['offset']:0;
        $where['limit'] = $params['limit']<=config('web.pre_page_size')?$params['limit']:config('web.pre_page_size');
        $orderList = OrderBuyout::getList($where);
        return apiResponse(['size'=>$sumCount,'list'=>$orderList],ApiStatus::CODE_0);
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
        $params= filter_array($params,[
            'goods_no'=>'required',
            'user_id'=>'required',
        ]);
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
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $orderInfo = $this->OrderRepository->get_order_info(['order_no'=>$goodsInfo['order_no'],"user_id"=>$params['user_id']]);
        if(empty($orderInfo)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        //验证商品是否冻结
        if($orderInfo['freeze_type']>0){
            return apiResponse([],ApiStatus::CODE_20001,"该订单当前状态不能买断");
        }

        //获取订单商品服务时间
        $this->OrderGoodsUnitRepository = new OrderGoodsUnitRepository;
        $goodsServe = $this->OrderGoodsUnitRepository->getGoodsUnitInfo($params['goods_no']);
        //按天处理
        if($goodsServe['unit'] == 1){
            $triggerTime = cofig("web.day_expiry_process_days");
        }
        //按月处理
        elseif($goodsServe['unit'] == 2){
            $triggerTime = cofig("web.month_expiry_process_days");
        }
        $newTime = $goodsServe['end_time']-$triggerTime;
        if($newTime>time()){
            return apiResponse([],ApiStatus::CODE_20001,"该订单未到买断时间");
        }

        //创建买断单
        $data = [
            'buyout_no'=>createNo(8),
            'order_no'=>$goodsInfo['order_no'],
            'goods_no'=>$goodsInfo['goods_no'],
            'user_id'=>$goodsInfo['user_id'],
            'buyout_price'=>$goodsInfo['buyout_price'],
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        $goods = [
            'goods_status' => OrderGoodStatus::BUY_OFF,
            'business_no' => $data['buyout_no'],
        ];
        $ret = $this->OrderGoodsRepository->update(['id'=>$goodsInfo['id']],$goods);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
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
        $params= filter_array($params,[
            'goods_no'=>'required',
            'user_id'=>'required',
            'buyout_price'=>'required',
        ]);
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
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $orderInfo = $this->OrderRepository->get_order_info(['order_no'=>$goodsInfo['order_no'],"user_id"=>$goodsInfo['user_id']]);
        if(empty($orderInfo)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        //验证商品是否冻结
        if($orderInfo['freeze_type']>0){
            return apiResponse([],ApiStatus::CODE_20001,"该订单当前状态不能买断");
        }
        //获取剩余未支付租金
        $where[] = ['status','=', \App\Order\Modules\Inc\OrderInstalmentStatus::UNPAID];
        $instaulment = OrderInstalmentRepository::getSumAmount($where);
        $fenqiAmount = $instaulment['amount'];
        //创建买断单
        $data = [
            'buyout_no'=>createNo(8),
            'order_no'=>$goodsInfo['order_no'],
            'goods_no'=>$goodsInfo['goods_no'],
            'user_id'=>$goodsInfo['user_id'],
            'plat_id'=>$params['user_id'],
            'buyout_price'=>$params['buyout_price']?$params['buyout_price']+$fenqiAmount:$goodsInfo['buyout_price']+$fenqiAmount,
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        $goods = [
            'goods_status' => OrderGoodStatus::BUY_OFF,
            'business_no' => $data['buyout_no'],
        ];
        $ret = $this->OrderGoodsRepository->update(['id'=>$goodsInfo['id']],$goods);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
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
        $params= filter_array($params,[
            'goods_no'=>'required',
            'user_id'=>'required',
        ]);
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
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $orderInfo = $this->OrderRepository->get_order_info(['order_no'=>$goodsInfo['order_no'],"user_id"=>$goodsInfo['user_id']]);
        if(empty($orderInfo)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        if($orderInfo['freeze_type']!=OrderFreezeStatus::Buyout){
            return apiResponse([],ApiStatus::CODE_50001,"该订单不在买断状态");
        }
        //获取买断单
        $buyout = OrderBuyout::getInfo($goodsInfo['goods_no']);
        if($buyout['status']!=OrderBuyoutStatus::OrderInitialize){
            return apiResponse([],ApiStatus::CODE_50001,"该订单不能取消买断");
        }
        //解冻订单-执行取消操作
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Non);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"订单解冻失败");
        }
        $ret = OrderBuyout::cancel($buyout['id'],$params['user_id']);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"取消失败");
        }
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
    public function pay(){
        if (empty($params['goods_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"goods_no必须");
        }
        if (empty($params['user_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"user_id必须");
        }
        //获取买断单
        $buyout = OrderBuyout::getInfo($params['goods_no'],$params['user_id']);
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
            'businessType'=>''.OrderStatus::BUSINESS_BUYOUT,
            'businessNo'=>$buyout['buyout_no'],
            'paymentAmount'=>$buyout['buyout_price'],
            'paymentFenqi'=>0,
        ];
        $payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payInfo);


    }
    /*
     * 支付完成
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "goods_no"=>"",商品编号
     * ]
     * @return json
     */
    public function paid(Request $request){
        //接收请求参数
        $orders =$request->all();
        $params = $orders['params'];
        //过滤参数
        $params= filter_array($params,[
            'goods_no'=>'required',
            'user_id'=>'required',
        ]);
        if (empty($params['goods_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"goods_no必须");
        }
        if (empty($params['user_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"user_id必须");
        }
        //获取买断单
        $buyout = OrderBuyout::getInfo($params['goods_no'],$params['user_id']);
        if(!$buyout){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        if($buyout['status']==OrderBuyoutStatus::OrderPaid){
            return apiResponse([],ApiStatus::CODE_0,"该订单已支付");
        }
        //更新买断单
        $ret = OrderBuyout::paid($buyout['id'],$params['user_id']);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"更新买断单为已支付失败！");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
    /*
     * 买断完成
     * @param array $params 【必选】
     * [
     *      "user_id"=>"", 用户id
     *      "goods_no"=>"",商品编号
     * ]
     * @return json
     */
    public function over(Request $request){
        //接收请求参数
        $orders =$request->all();
        $params = $orders['params'];
        //过滤参数
        $params= filter_array($params,[
            'goods_no'=>'required',
            'user_id'=>'required',
        ]);
        if (empty($params['goods_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"goods_no必须");
        }
        if (empty($params['user_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"user_id必须");
        }
        //获取买断单
        $buyout = OrderBuyout::getInfo($params['goods_no'],$params['user_id']);
        if(!$buyout){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        if($buyout['status']==OrderBuyoutStatus::OrderRelease){
            return apiResponse([],ApiStatus::CODE_0,"该订单已完成");
        }
        //获取订单商品信息
        $this->OrderGoodsRepository = new OrderGoodsRepository;
        $goodsInfo = $this->OrderGoodsRepository->getGoodsInfo($params['goods_no']);
        if(empty($goodsInfo)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        //解冻订单
        $ret = $this->OrderRepository->orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Non);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"订单解冻失败");
        }
        //更新订单商品
        $goods = [
            'goods_status' => OrderGoodStatus::BUY_OUT,
            'business_no' => $buyout['buyout_no'],
        ];
        $ret = $this->OrderGoodsRepository->update(['id'=>$goodsInfo['id']],$goods);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"更新订单商品状态为买断完成失败！");
        }
        //更新买断单
        $ret = OrderBuyout::over($buyout['id'],$params['user_id']);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"更新买断单为已解押失败！");
        }
        return apiResponse([],ApiStatus::CODE_0);
    }
}
