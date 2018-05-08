<?php
namespace App\Warehouse\Modules\Service;

use App\Warehouse\Models\Delivery;
use App\Warehouse\Modules\Repository\DeliveryRepository;
use App\Warehouse\Modules\Repository\ThirdInterface;
use Illuminate\Support\Facades\DB;

class DeliveryCreater
{

    protected $third;
    protected $deliveryRepository;

    public function __construct(ThirdInterface $third,DeliveryRepository $deliveryRepository)
    {
        $this->third = $third;
        $this->orderRepository = $deliveryRepository;
    }

    /**
     * 创建发货单
     * @return bool
     */
    public function create($data)
    {
        //18位发货单号
        $rand_no =rand(1000,9999);
        $delivery_no = date('YmdHis',time()).$rand_no;

        DB::beginTransaction();
        try {
            $this->third->GetFengkong();
            $this->third->GetUser();
            $this->third->GetSku();
            var_dump('创建...');
            var_dump('编号：' . $delivery_no);
            DB::commit();
            die;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();die;
        }

    }
}