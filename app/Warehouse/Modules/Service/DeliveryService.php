<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 14:36
 */


namespace App\Warehouse\Modules\Service;

use App\Warehouse\Models\Delivery;
use App\Warehouse\Models\DeliveryGoods;
use App\Warehouse\Modules\Repository\DeliveryRepository;
use Illuminate\Support\Facades\Log;

class DeliveryService
{

    const TIME_TYPE_CREATE = 'create';//创建时间
    const TIME_TYPE_DELIVERY = 'delivery'; //发货时间
    const TIME_TYPE_NONE = 'none'; //不限时间



    //查找类型
    const SEARCH_MOBILE = 'mobile';//手机
    const SEARCH_ORDER_NO = 'order_no';//订单号
    const SEARCH_DELIVERY_NO = 'delivery_no';//订单号


    static $searchs = [
        self::SEARCH_MOBILE => 'customer_mobile',
        self::SEARCH_ORDER_NO => 'order_no',
        self::SEARCH_DELIVERY_NO => 'delivery_no'
    ];

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


    public function match($delivery_no)
    {
        if (!DeliveryRepository::match($delivery_no)) {
            throw new \Exception('配货操作失败');
        }
    }


    /**
     * @param $params
     * 配货
     * $params devivery_no, serial_no, quantity, imeis(imeis为数组格式，可能一设备对应多imei)
     */
    public function matchGoods($params)
    {
        if (!DeliveryRepository::matchGoods($params)) {
            throw new \Exception('配货失败,请重新操作');
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

    /**
     * @param $params
     * @return array
     * @throws \Exception
     *
     * 列表
     */
    public function list($params)
    {
        $limit = 20;
        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];


        //1：待配货；2：待发货；3：已发货，待用户签收；4：已签收完成；5：已拒签完成；6：已取消；
        if (isset($params['status']) && $params['status']) {
            $whereParams['status'] = $params['status'];
        }

//        if (isset($params['order_no']) && $params['order_no']) {
//            $whereParams['order_no'] = $params['order_no'];
//        }
        if (isset($params['delivery_no']) && $params['delivery_no']) {
            $whereParams['delivery_no'] = $params['delivery_no'];
        }

        $search = $this->paramsSearch($params);
        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $time_type   = isset($params['time_type']) ? $params['time_type'] : 'none';

        $logic_params = [];
        if ($time_type != 'none') {
            if (!isset($params['time_begin']) || !$params['time_begin']) {
                throw new \Exception('请填写开始时间');
            }

            if (!isset($params['time_end']) || !$params['time_end']) {
                throw new \Exception('请填写结束时间');
            }

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
            return ['data'=>[], 'per_page'=>$limit, 'total'=>0, 'currentPage'=>0];
        }

        $show_detail = isset($params['detail']) ? $params['detail'] : false;

        if (!$show_detail) {
            return ['data'=>$items, 'per_page'=>$limit, 'total'=>$collect->total(), 'current_page'=>$collect->currentPage()];
        }

        $result = [];
        foreach ($items as $item) {
            $it = $item->toArray();
            $it['logistics_name'] = $this->getLogisticsName($it['logistics_id']);
            $it['status_mark'] = $item->getStatus();
            $it['imeis'] = $item->imeis->toArray();
            $it['goods'] = $item->goods->toArray();
            array_push($result, $it);
        }

        return ['data'=>$result, 'per_page'=>$limit, 'total'=>$collect->total(), 'current_page'=>$collect->currentPage()];

    }

    /**
     * @param $id
     * @param $no
     * 取物流名
     */
    public function getLogisticsName($id)
    {
        return '顺风';
    }

    /**
     * 查找类型
     */
    public function paramsSearch($params)
    {
        if (!isset($params['kw_type']) || !$params['kw_type']) {
            return false;
        }

        if (!isset($params['keywords']) || !$params['keywords']) {
            return false;
        }

        return [self::$searchs[$params['kw_type']] => $params['keywords']];
    }


    public function getOrderNoByDeliveryNo($delivery_no)
    {
        return DeliveryRepository::getOrderNoByDeliveryNo($delivery_no);
    }

}