<?php
namespace App\Warehouse\Modules\Service;

use App\Warehouse\Modules\Repository\DeliveryRepository;

class DeliveryCreater
{


    /**
     * 发货单创建信息查询
     * @return bool
     */
    public function confirmation($data)
    {
        //创建发货单
        if (!DeliveryRepository::create($data)) {
            throw new \Exception('创建发货单失败');
        }
    }



}