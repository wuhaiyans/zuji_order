<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 14:36
 */


namespace App\Warehouse\Modules\Service;

use App\Warehouse\Modules\Repository\DeliveryRepository;
use function Couchbase\defaultDecoder;

class DeliveryService
{

    const TIME_TYPE_CREATE = 'create';//创建时间
    const TIME_TYPE_DELIVERY = 'delivery'; //发货时间
    const TIME_TYPE_NONE = 'none'; //不限时间

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


    public function cancelDelivery($delivery_no)
    {
        if (!DeliveryRepository::cancelDelivery($delivery_no)) {
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
    public function send($delivery_no)
    {
        if (!DeliveryRepository::send($delivery_no)) {
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





    public function list($params)
    {
        $limit = 20;
        if (isset($params['limit']) && $params['limit']) {
            $limit = $params['limit'];
        }
        $whereParams = [];

        if (isset($params['order_no']) && $params['order_no']) {
            $whereParams['order_no'] = $params['order_no'];
        }

        if (isset($params['delivery_no']) && $params['delivery_no']) {
            $whereParams['delivery_no'] = $params['delivery_no'];
        }
        $page = isset($params['page']) ? $params['page'] : null;


        $time_type   = isset($params['time_type']) ? $params['time_type'] : 'none';

        if ($time_type != 'none') {
            if (!isset($params['time_begin']) || !$params['time_begin']) {
                throw new \Exception('请填写开始时间');
            }

            if (!isset($params['time_end']) || !$params['time_end']) {
                throw new \Exception('请填写结束时间');
            }

            $logic_params = [];

            switch ($time_type) {
                case self::TIME_TYPE_CREATE:
                    array_push($logic_params, ['create_time', '<=', strtotime($params['time_end'])]);
                    array_push($logic_params, ['create_time', '>=', strtotime($params['time_begin'])]);
                    break;

                case self::TIME_TYPE_DELIVERY:
                default:
                    array_push($logic_params, ['delivery_time', '<=', strtotime($params['time_end'])]);
                    array_push($logic_params, ['delivery_time', '>=', strtotime($params['time_begin'])]);
            }
        }

        $collect = DeliveryRepository::list($whereParams, $logic_params, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return [];
        }

        $show_detail = isset($params['detail']) ? $params['detail'] : false;

        if (!$show_detail) {
            return ['data'=>$items, 'limit'=>$limit, 'page'=>$page];
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


    public function getOrderNoByDeliveryNo($delivery_no)
    {
        return DeliveryRepository::getOrderNoByDeliveryNo($delivery_no);
    }

}