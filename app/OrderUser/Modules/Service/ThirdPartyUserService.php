<?php
/**
 * User: jinlin wang
 * Date: 2018/8/1
 * Time: 16:50
 */
namespace App\OrderUser\Modules\Service;

use App\OrderUser\Modules\Repository\ThirdPartyUserRepository;
use App\Warehouse\Models\Imei;
use App\Warehouse\Modules\Repository\ImeiRepository;

class ThirdPartyUserService
{

    /**
     * 导入数据
     */
    public static function import($data)
    {
        if (!ImeiRepository::import($data)) {
            throw new \Exception('导入imei数据失败');
        }
    }


    /**
     * 列表仓库
     * @param $params
     * @return array
     */
    public static function lists($params)
    {
        $limit = 20;

        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];


        $search = self::paramsSearch($params);

        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }


        $page = isset($params['page']) ? $params['page'] : 1;

        $collect = ThirdPartyUserRepository::lists($whereParams, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return [
                'data'=>[], 'size'=>$limit, 'page'=>$collect->currentPage(), 'total'=>$collect->total()
            ];
        }

        return ['data'=>$items, 'size'=>$limit, 'page'=>$collect->currentPage(), 'total'=>$collect->total()];
    }

    /**
     * 查找类型
     */
    public static function paramsSearch($params)
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