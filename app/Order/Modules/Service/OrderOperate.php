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
use App\Lib\ApiStatus;


class OrderOperate
{
    protected $third;
    public function __construct(ThirdInterface $third)
    {

        $this->third = $third;
    }

    /**
     * 取消订单
     */
    public static function cancelOrder($orderId)
    {
        if (empty($orderId)) {
            return false;
            }
        //开启事物
        DB::beginTransaction();
        try {
            //关闭订单状态
            $orderData =  OrderRepository::closeOrder($orderId);
            if (!$orderData) {
                DB::rollBack();
               return ApiStatus::CODE_31002;
            }
            //释放库存
            //查询商品的信息
            $orderGoods = OrderRepository::getGoodsListByOrderId($orderId);
            if ($orderGoods) {
                foreach ($orderGoods as $orderGoodsValues){
                    //暂时一对一
//                    $stockDelta[] = [
//                        'goodsId'=>$orderGoodsValues['good_id'],
//                        'prod_id'=>$orderGoodsValues['prod_id'],
//                        'quantity'=>$orderGoodsValues['quantity'],
//                    ];
                    $goodsId = $orderGoodsValues['good_id'];
                    $prod_id = $orderGoodsValues['prod_id'];
                }
                $success = $this->third->AddStock($prod_id, $goodsId);
                if (!$success) {
                    DB::rollBack();
                    return ApiStatus::CODE_31003;
                }
            }
            //优惠券归还
            //分期关闭

            return ApiStatus::CODE_31003;

        } catch (\Exception $exc) {
            DB::rollBack();
            return  ApiStatus::CODE_31006;
        }

    }


}