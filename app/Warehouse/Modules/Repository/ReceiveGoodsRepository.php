<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 13:48
 */

namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Models\ReceiveGoods;
use Illuminate\Support\Facades\Log;

class ReceiveGoodsRepository
{

    const SEARCH_TYPE_MOBILE='customer_mobile';
    const SEARCH_TYPE_ORDER_NO = 'order_no';
    const SEARCH_TYPE_GOODS_NAME = 'name';


    /**
     * @param $params
     * @param $limit
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * 列表
     */
    public static function list($params,$logic_params, $limit, $page=null, $type)
    {
        $query = ReceiveGoods::whereHas('receive', function ($query) use($params) {
            if (is_array($params)) {
                foreach ($params as $k => $v) {
                    if (in_array($k, [self::SEARCH_TYPE_MOBILE, self::SEARCH_TYPE_ORDER_NO])) {
                        $query->where('zuji_receive.' . $k,'like', '%'.$v.'%');
                    }
                }
            }
        });

        if (isset($params['name']) && $params['name'] == self::SEARCH_TYPE_GOODS_NAME) {
            $query->where('name', 'like', '%'.$params['name'].'%');
        }

        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        } else {

            if ($type == 1) {
                $query->whereIn('status', [ReceiveGoods::STATUS_ALL_RECEIVE, ReceiveGoods::STATUS_ALL_CHECK]);
            }

            if ($type = 2) {
                $query->whereIn('status', [ReceiveGoods::STATUS_ALL_RECEIVE, ReceiveGoods::STATUS_INIT]);
            }

        }

        return $query->with(['receive'])->paginate($limit,
            [
                '*'
            ],
            'page', $page);
    }



}