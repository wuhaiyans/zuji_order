<?php
namespace App\Order\Modules\Service;

use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ThirdInterface;
use Illuminate\Support\Facades\DB;

class OrderCreater
{

    protected $third;
    protected $orderRepository;

    public function __construct(ThirdInterface $third,OrderRepository $orderRepository)
    {
        $this->third = $third;
        $this->orderRepository = $orderRepository;
    }

    /**
     * 创建订单
     * @return bool
     */
    public function create($data)
    {
        $order_no =rand(10000000000000,99999999999999999);

        DB::beginTransaction();
        try {
            $this->third->GetFengkong();
            $this->third->GetUser();
            $this->third->GetSku();
            var_dump('创建订单...');
            var_dump('订单编号：' . $data['order_no']);
            DB::commit();
            die;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();die;
        }

    }
}