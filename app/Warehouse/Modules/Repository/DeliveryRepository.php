<?php
namespace App\Warehouse\Modules\Repository;
use App\Warehouse\Models\Delivery;

class DeliveryRepository
{

    private $delivery;

    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }
    public function create(){


    }

}