<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 13:48
 */

namespace App\Warehouse\Modules\Repository;

class ReceiveGoodsRepository
{
    /**
     * @param $params
     * @param $limit
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * 列表
     */
    public static function list($params,$logic_params, $limit, $page=null)
    {

        $query = \App\Warehouse\Models\Imei::whereIn('status', [Imei::STATUS_OUT, Imei::STATUS_IN]);

        if (is_array($params)) {
            foreach ($params as $k => $param) {
                if (in_array($k, ['imei', 'brand', 'color', 'business'])) {
                    $query->where($k, 'like', '%'.$param.'%');
                } else {
                    $query->where([$k=>$param]);
                }
            }
        }

        if (is_array($logic_params) && count($logic_params)>0) {
            foreach ($logic_params as $logic) {
                $query->where($logic[0], $logic[1] ,$logic[2]);
            }
        }

        return $query->paginate($limit,
            [
                '*'
            ],
            'page', $page);
    }
}