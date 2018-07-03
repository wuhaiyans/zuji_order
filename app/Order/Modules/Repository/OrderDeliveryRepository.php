<?php
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use App\Lib\Goods\Goods;
use App\Order\Models\Order;
use App\Order\Models\OrderDelivery;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderLog;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Service\OrderInstalment;
use Illuminate\Support\Facades\DB;

class OrderDeliveryRepository
{

    protected $orderDelivery;


    public function __construct(OrderDelivery $orderDelivery)
    {
        $this->orderDelivery = $orderDelivery;
    }

    /**
     * heaven
     * 获取订单发货信息
     * @param $orderNo 订单号
     * @return array|bool
     */
    public static function getOrderDelivery($orderNo)
    {
        if (empty($orderNo)) return false;
        $result = OrderDelivery::query()->where([
            ['order_no', '=', $orderNo],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }


}