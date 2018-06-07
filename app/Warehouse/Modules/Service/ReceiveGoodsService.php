<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 13:49
 */

namespace App\Warehouse\Modules\Service;

use App\Warehouse\Models\ReceiveGoods;
use App\Warehouse\Models\ReceiveGoodsImei;
use App\Warehouse\Modules\Repository\ReceiveGoodsRepository;
use Illuminate\Support\Facades\Log;

class ReceiveGoodsService
{


    /**
     * @param $params
     * @return array
     * @throws \Exception
     *
     * 获取收货设备列表
     */
    public function list($params)
    {
        $limit = 20;
        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];


        $search = $this->paramsSearch($params);
        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        //组合时间查询
        $logic_params = [];
        if (isset($params['begin_time']) && $params['begin_time']) {
            array_push($logic_params, ['check_time', '>=', strtotime($params['begin_time'])]);
        }

        if (isset($params['end_time']) && $params['end_time']) {
            array_push($logic_params, ['check_time', '<=', strtotime($params['end_time'])]);
        }


        $collect = ReceiveGoodsRepository::list($whereParams, $logic_params, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return ['data'=>[], 'per_page'=>$limit, 'total'=>0, 'current_page'=>0];
        }

        $result = $receiveNos = [];
        foreach ($items as $item) {
            $it = $item->toArray();
            array_push($receiveNos, $item->receive_no);

            $it['order_no'] = $item->receive->order_no;
            $it['receive_time'] = $item->receive->receive_time;
            $it['imei'] = '';
            $it['serial_number'] = '';

            array_push($result, $it);
        }

        //查询imei
        $imeis = ReceiveGoodsImei::whereIn('receive_no', $receiveNos)->get()->toArray();

        if ($imeis) {//组合 imei
            foreach ($result as &$item) {
                foreach ($imeis as $imei) {
                    if ($imei['goods_no'] == $item['goods_no']) {
                        $item['imei'] = $imei['imei'];
                        $item['serial_number'] = $imei['serial_number'];
                    }
                }
            }unset($item);
        }

        return [
            'data'=>$result,
            'per_page'=>$limit,
            'total'=>$collect->total(),
            'current_page'=>$collect->currentPage(),
        ];

    }


    public static function searchKws()
    {
        $ks = [
            ReceiveGoodsRepository::SEARCH_TYPE_MOBILE    => '手机号',
            ReceiveGoodsRepository::SEARCH_TYPE_ORDER_NO  => '订单号',
            ReceiveGoodsRepository::SEARCH_TYPE_GOODS_NAME=> '设备名',
        ];

        return $ks;
    }


    /**
     * 查找字段整理
     * @param $params
     * @return array|bool
     */
    public function paramsSearch($params)
    {
        if (!isset($params['kw_type']) || !$params['kw_type']) {
            return false;
        }

        if (!isset($params['keywords']) || !$params['keywords']) {
            return false;
        }

        return [$params['kw_type'] => $params['keywords']];
    }
}