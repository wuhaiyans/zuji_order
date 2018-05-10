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

    /**
     * @param $params
     * @param $limit
     * @param $page
     * @return array
     *
     * 列表
     */
    public function list($params, $limit, $page)
    {
        $collect = ReceiveRepository::list($params, $limit, $page);
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
    public function calcelReceive($receive_no)
    {
        if (!ReceiveRepository::cancelReceive($receive_no)) {
            throw new \Exception($receive_no . '取消签收失败');
        }
    }

    /**
     * 检测
     */
    public function check()
    {

    }

    /**
     * 取消检测
     */
    public function cancelCheck()
    {

    }
    public function finishCheck()
    {

    }
    public function show()
    {

    }

    public function note()
    {

    }
}