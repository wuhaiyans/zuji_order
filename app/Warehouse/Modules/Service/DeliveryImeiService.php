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

    /**
     * @param $delivery_no
     * @param $imei
     * @param $serial_no
     * @throws \Exception
     * 添加
     */
    public function add($delivery_no, $imei, $serial_no)
    {
        if (!DeliveryImeiRepository::add($delivery_no, $imei, $serial_no)) {
            throw new \Exception('添加imei失败');
        }
    }

    /**
     * @param $delivery_no
     * @param $imei
     * @throws \Exception
     * 删除
     */
    public function del($delivery_no, $imei)
    {
        if (!DeliveryImeiRepository::del($delivery_no, $imei)) {
            throw new \Exception('删除imei失败');
        }
    }

}