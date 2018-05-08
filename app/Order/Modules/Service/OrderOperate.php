<?php
/**
 *    订单操作类
 *    author: heaven
 *    date : 2018-05-04
 */
namespace App\Order\Modules\Service;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ThirdInterface;
use Illuminate\Support\Facades\DB;
use App\Order\Modules\Service\OrderInstalment;
use App\Lib\ApiStatus;


class OrderOperate
{
    protected $third;
    protected $orderInstal;
    public function __construct(ThirdInterface $third)
    {
        $this->third = $third;
    }

    /**
     * @param string $orderNo 订单编号
     * @
     * 取消订单
     */
    public static function cancelOrder($orderNo,$userId='')
    {
        if (empty($orderNo)) {
            return false;
            }
        //开启事物
        DB::beginTransaction();
        try {

            //关闭订单状态
            $orderData =  OrderRepository::closeOrder($orderNo,$userId);
            if (!$orderData) {
                DB::rollBack();
               return ApiStatus::CODE_31002;
            }
            //释放库存
            //查询商品的信息
            $orderGoods = OrderRepository::getGoodsListByOrderId($orderNo);
            if ($orderGoods) {
                foreach ($orderGoods as $orderGoodsValues){
                    $stockDelta[] = [
                        'goodsId'=>$orderGoodsValues['good_id'],
                        'prod_id'=>$orderGoodsValues['prod_id'],
                        'quantity'=>$orderGoodsValues['quantity'],
                    ];
                    $goodsId = $orderGoodsValues['good_id'];
                    $prod_id = $orderGoodsValues['prod_id'];
                }
                $success =$this->third->AddStock($prod_id, $goodsId);

            }

            if (!$success || empty($orderGoods)) {
                DB::rollBack();
                return ApiStatus::CODE_31003;
            }
            //优惠券归还
           $success =  $this->third->setCoupon(['user_id'=>$userId ,'coupon_id'=>$orderNo]);
            if (!$success) {
                DB::rollBack();
                return ApiStatus::CODE_31003;
            }
            //分期关闭
           $success =  OrderInstalment::close(['order_no'=>$orderNo]);
             if (!$success) {
                 DB::rollBack();
                 return ApiStatus::CODE_31004;
             }
            return ApiStatus::CODE_0;

        } catch (\Exception $exc) {
            DB::rollBack();
            return  ApiStatus::CODE_31006;
        }

    }

    /**
     * @param $orderType :1,线上; 2,线下  3,小程序
     *  生成订单号
     */
    public static function createOrderNo($orderType=1){
        $year = array();
        for($i=65;$i<91;$i++){
            $year[]= strtoupper(chr($i));
        }
        $orderSn = $year[(intval(date('Y')))-2018] . strtoupper(dechex(date('m'))) . date('d') .$orderType. substr(time(), -5) . substr(microtime(), 2, 5) . rand(0, 9);
        return $orderSn;
    }

    /**
     * 获取订单详情数据
     * @param $orderNo string 订单编号
     * @return array|\Illuminate\Http\JsonResponse
     */
    public static function getOrderInfo($orderNo)
    {
        $order = array();
        if (empty($orderNo))   return apiResponse([],ApiStatus::CODE_32001,ApiStatus::$errCodes[ApiStatus::CODE_32001]);
        //查询订单和用户发货的数据
        $orderData =  OrderRepository::getOrderInfo(array('orderNo'=>$orderNo));
        if (empty($orderData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['order_info'] = $orderData;
        //订单商品列表相关的数据
        $goodsData =  OrderRepository::getGoodsListByOrderId(array('orderNo'=>$orderNo));
        if (empty($goodsData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['goods_info'] = $goodsData;
        //设备扩展信息表
        $goodsExtendData =  OrderRepository::getGoodsExtendInfo(array('orderNo'=>$orderNo));
//        if (empty($goodsExtendData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        $order['goods_extend_info'] = $goodsExtendData;
        //分期信息,根据订单号查询分期数据
//        OrderInstalment::queryList(array(order))
//        $goodsExtendData =  OrderRepository::getGoodsExtendInfo(array('orderNo'=>$orderNo));
//        if (empty($goodsExtendData)) return apiResponseArray(ApiStatus::CODE_32002,[]);
        return apiResponseArray(ApiStatus::CODE_0,$order);

    }

    /**
     *
     */
    public static function getOrderList($param = array())
    {
        //根据用户id查找订单列表

        $orderList = OrderRepository::getOrderList(array('user_id'=>18));
        return apiResponseArray(ApiStatus::CODE_0,$orderList);


    }


}