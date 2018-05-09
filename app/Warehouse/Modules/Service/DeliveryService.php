<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 14:36
 */


namespace App\Warehouse\Modules\Service;

use App\Warehouse\Modules\Repository\DeliveryRepository;

class DeliveryService
{

    /**
     * @param $order_no
     * @throws \Exception
     * 取消
     */
    public function cancel($order_no)
    {
        if (!DeliveryRepository::cancel($order_no)) {
            throw new \Exception('取消发货失败');
        }
    }


    /**
     * @param $delivery_no
     * @param bool $auto
     * @throws \Exception
     * 签收
     */
    public function receive($delivery_no, $auto=false)
    {
        if (!DeliveryRepository::receive($delivery_no, $auto)) {
            throw new \Exception('签收失败');
        }
    }

    /**
     * @param $delivery_no
     * @return array
     * @throws \Exception
     * 清单明细
     */
    public function detail($delivery_no)
    {
        if (!($detail = DeliveryRepository::detail($delivery_no))) {
            throw new \Exception('发货单' . $delivery_no . '不存在');
        }

        return $detail;
    }

    /**
     * @param $delivery_no
     * @return mixed
     * @throws \Exception
     * 清单imei
     */
    public function imeis($delivery_no)
    {
        if (!($imeis = DeliveryRepository::imeis($delivery_no))) {
            throw new \Exception('未找到imei');
        }

        return $imeis;
    }

    /**
     * @param $order_no
     * @throws \Exception
     * 发货操作
     */
    public function send($order_no)
    {
        if (!DeliveryRepository::send($order_no)) {
            throw new \Exception('发货操作失败');
        }
    }


    /**
     * @param $delivery_no
     * @param $logistics_id
     * @param $logistics_no
     * @throws \Exception
     * 修改物流
     */
    public function logistics($delivery_no, $logistics_id, $logistics_no)
    {
        if (!DeliveryRepository::logistics($delivery_no, $logistics_id, $logistics_no)) {
            throw new \Exception('修改物流失败');
        }
    }


    /**
     * @param $delivery_no
     * @throws \Exception
     * 取消配货
     */
    public function cancelMatch($delivery_no)
    {
        if (!DeliveryRepository::cancelMatch($delivery_no)) {
            throw new \Exception('取消配货失败');
        }
    }

    /**
     * @param $params
     * @param $limit
     * @param $page
     * @return array
     * 列表
     */
    public function list($params, $limit, $page)
    {
        $collect = DeliveryRepository::list($params, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return [];
        }

        $result = [];

        foreach ($items as $item) {

            $it = $item->toArray();

            $it['imeis'] = $item->imeis->toArray();
            $it['goods'] = $item->goods->toArray();


            array_push($result, $it);

        }

        return ['data'=>$result, 'limit'=>$limit, 'page'=>$page];
    }
}