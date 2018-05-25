<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\PublicFunc;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\ReturnStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderBuyout;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use Illuminate\Support\Facades\DB;

/**
 * 订单买断接口控制器
 * @var obj BuyoutController
 * @author limin<limin@huishoubao.com.cn>
 */

class BuyoutController extends Controller
{

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
        $goods_info = $this->OrderGoodsRepository->getGoodsInfo($params['goods_no']);
        if(empty($goods_info)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $order_info = $this->OrderRepository->get_order_info(['order_no'=>$goods_info['order_no'],"user_id"=>$params['user_id']]);
        if(empty($order_info)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        //验证商品是否冻结
        if($order_info['freeze_type']>0){
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
        $this->OrderBuyout = new OrderBuyout;
        $data = [
            'order_no'=>$goods_info['order_no'],
            'goods_no'=>$goods_info['goods_no'],
            'user_id'=>$goods_info['user_id'],
            'buyout_price'=>$goods_info['buyout_price'],
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        $goods = [
            'goods_status' => 1,
            'business_no' => createNo(8),
        ];
        $ret = $this->OrderGoodsRepository->update(['id'=>$goods_info['id']],$goods);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goods_info['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
        return apiResponse($goods_info,ApiStatus::CODE_0);
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
        $goods_info = $this->OrderGoodsRepository->getGoodsInfo($params['goods_no']);
        if(empty($goods_info)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $order_info = $this->OrderRepository->get_order_info(['order_no'=>$goods_info['order_no'],"user_id"=>$goods_info['user_id']]);
        if(empty($order_info)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        //验证商品是否冻结
        if($order_info['freeze_type']>0){
            return apiResponse([],ApiStatus::CODE_20001,"该订单当前状态不能买断");
        }

        //创建买断单
        $data = [
            'order_no'=>$goods_info['order_no'],
            'goods_no'=>$goods_info['goods_no'],
            'user_id'=>$goods_info['user_id'],
            'plat_id'=>$params['user_id'],
            'buyout_price'=>$params['buyout_price']?$params['buyout_price']:$goods_info['buyout_price'],
        ];
        $ret = OrderBuyout::create($data);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"买断单创建失败");
        }
        $goods = [
            'goods_status' => 1,
            'business_no' => createNo(8),
        ];
        $ret = $this->OrderGoodsRepository->update(['id'=>$goods_info['id']],$goods);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单商品状态失败");
        }
        $ret = $this->OrderRepository->orderFreezeUpdate($goods_info['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_20001,"更新订单状态失败");
        }
        return apiResponse($goods_info,ApiStatus::CODE_0);
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
        $goods_info = $this->OrderGoodsRepository->getGoodsInfo($params['goods_no']);
        if(empty($goods_info)){
            return apiResponse([],ApiStatus::CODE_50002,"没有找到该订单商品");
        }
        //获取订单信息
        $this->OrderRepository= new OrderRepository;
        $order_info = $this->OrderRepository->get_order_info(['order_no'=>$goods_info['order_no'],"user_id"=>$goods_info['user_id']]);
        if(empty($order_info)){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        if($order_info['freeze_type']!=OrderFreezeStatus::Buyout){
            return apiResponse([],ApiStatus::CODE_50001,"该订单不在买断状态");
        }
        //获取买断单
        $buyout = OrderBuyout::getInfo($goods_info['goods_no']);
        if($buyout['status']!=OrderBuyoutStatus::OrderInitialize){
            return apiResponse([],ApiStatus::CODE_50001,"该订单不能取消买断");
        }
        //解冻订单-执行取消操作
        $ret = $this->OrderRepository->orderFreezeUpdate($goods_info['order_no'],OrderFreezeStatus::Non);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"订单解冻失败");
        }
        $ret = OrderBuyout::cancel($buyout['id'],$params['user_id']);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50001,"取消失败");
        }
        return apiResponse($goods_info,ApiStatus::CODE_0);
    }
}
