<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderRisk;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderUserRisk;

class OrderUserAddressRepository
{

    public static function add($data){
        $data =OrderUserRisk::create($data);
        return $data->getQueueableId();
    }

}