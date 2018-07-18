<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 13:48
 */

namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Models\ReceiveGoods;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

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
                if (isset($params['return_type'])) {
                    $query->where('zuji_receive.type', '=', $params['return_type']);
                }
            }
        });

        if ( isset($params[self::SEARCH_TYPE_GOODS_NAME]) && $params[self::SEARCH_TYPE_GOODS_NAME] ) {
            $query->where('goods_name', 'like', '%'.$params['name'].'%');
        }

        if ($logic_params) {
            $query->where($logic_params);
        }

        if (isset($params['status']) && $params['status']!='') {
            $query->where('status', '=', $params['status']);
        } else {

            return $type;
            if ($type == 1) {
                $query->whereIn('status', [ReceiveGoods::STATUS_ALL_RECEIVE, ReceiveGoods::STATUS_ALL_CHECK]);
            }

            if ($type == 2) {
                $query->whereIn('status', [ReceiveGoods::STATUS_ALL_RECEIVE, ReceiveGoods::STATUS_INIT]);
            }

        }
        $query->orderByDesc('id');

        return $query->with(['receive'])->paginate($limit,
            [
                '*'
            ],
            'page', $page);
    }

    /**
     * 取消收货清单
     */
    public static function cancel($receive_no){
        $model = ReceiveGoods::where(['receive_no'=>$receive_no])->first();

        if (!$model) {
            throw new NotFoundResourceException('收货清单' . $receive_no . '未找到');
        }

        if ($model->status != ReceiveGoods::STATUS_INIT) {
            throw new \Exception('收货清单' . $receive_no . '非待验收状态，取消收货失败');
        }

        $model->status = ReceiveGoods::STATUS_NONE;
        $model->status_time = time();

        return $model->update();
    }



}