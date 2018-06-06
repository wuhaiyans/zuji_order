<?php
namespace App\Order\Modules\Repository;



use App\Order\Models\OrderUserCertified;

class OrderUserCertifiedRepository
{

    public static function add($data){
        $data =OrderUserCertified::create($data);
        return $data->getQueueableId();
    }

}