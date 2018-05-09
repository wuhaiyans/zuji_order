<?php
namespace App\Warehouse\Modules\Service;

use App\Warehouse\Models\Delivery;
use App\Warehouse\Modules\Repository\DeliveryRepository;
use App\Warehouse\Modules\Repository\ThirdInterface;

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
     * 发货单创建信息查询
     * @return bool
     */
    public function confirmation($data)
    {
        //创建发货单
        $this->orderRepository->create($data);

    }
}