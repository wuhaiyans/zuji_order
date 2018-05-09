<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 15:41
 */


namespace App\Warehouse\Modules\Service;

use App\Warehouse\Modules\Repository\DeliveryImeiRepository;

class DeliveryImeiService
{

    public function add($delivery_no, $imei, $serial_no)
    {
        if (!DeliveryImeiRepository::add($delivery_no, $imei, $serial_no)) {
            throw new \Exception('添加imei失败');
        }
    }

    public function del($delivery_no, $imei)
    {
        if (!DeliveryImeiRepository::del($delivery_no, $imei)) {
            throw new \Exception('添加imei失败');
        }
    }

}