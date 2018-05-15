<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:18
 */


namespace App\Warehouse\Modules\Service;

use App\Warehouse\Modules\Repository\ReceiveRepository;

class ReceiveService
{

    const TIME_TYPE_CREATE = 'create';//创建时间
    const TIME_TYPE_RECEIVE = 'receive'; //发货时间
    const TIME_TYPE_NONE = 'none'; //不限时间

    /**
     * @param $params
     * @param $limit
     * @param $page
     * @return array
     *
     * 列表
     */
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

                case self::TIME_TYPE_RECEIVE:
                default:
                    array_push($logic_params, ['receive_time', '<=', strtotime($params['time_end'])]);
                    array_push($logic_params, ['receive_time', '>=', strtotime($params['time_begin'])]);
            }
        }

        $collect = ReceiveRepository::list($whereParams, $logic_params, $limit, $page);
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


    /**
     * 创建
     */
    public function create($data)
    {
        if (!ReceiveRepository::create($data)) {
            throw new \Exception('收货单创建失败');
        }
    }

    /**
     * @param $receive_no
     * @throws \Exception
     * 取消收货
     */
    public function cancel($receive_no)
    {
        if (!ReceiveRepository::cancel($receive_no)) {
            throw new \Exception('取消收货单失败');
        }
    }


    public function received($receive_no)
    {
        if (!ReceiveRepository::received($receive_no)) {
            throw new \Exception($receive_no . '号收货单签收失败');
        }
    }

    /**
     * @param $receive_no
     * 取消签收
     */
    public function cancelReceive($receive_no)
    {
        if (!ReceiveRepository::cancelReceive($receive_no)) {
            throw new \Exception($receive_no . '取消签收失败');
        }
    }

    /**
     * 检测
     */
    public function check($receive_no, $serial_no, $data)
    {
        if (!ReceiveRepository::check($receive_no, $serial_no, $data)) {
            throw new \Exception($receive_no . '设备:'.$serial_no.'验签失败');
        }
    }

    /**
     * 取消检测
     */
    public function cancelCheck()
    {

    }

    /**
     * 检测完成
     */
    public function finishCheck($receive_no)
    {
        $receive = ReceiveRepository::finishCheck($receive_no);
        if (!$receive) {
            throw new \Exception($receive_no . '检测完成操作失败');
        }
        return $receive;
    }

    /**
     * @param $receive_no
     * @return array
     * @throws \Exception
     * 清单
     */
    public function show($receive_no)
    {
        $detail = ReceiveRepository::show($receive_no);

        if (!$detail) {
            throw new \Exception('收货单' . $receive_no . '不存在');
        }

        return $detail;
    }

    public function note()
    {

    }
}