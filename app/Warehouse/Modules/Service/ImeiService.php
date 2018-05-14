<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 13:50
 */

namespace App\Warehouse\Modules\Service;

use App\Warehouse\Models\Imei;
use App\Warehouse\Modules\Repository\ImeiRepository;

class ImeiService
{
    /**
     * 导入数据
     */
    public function import($data)
    {
        if (!ImeiRepository::import($data)) {
            throw new \Exception('导入imei数据失败');
        }
    }


    public function list($params)
    {
        $limit = 20;

        if (isset($params['limit']) && $params['limit']) {
            $limit = $params['limit'];
        }
        $whereParams = [];

        if (isset($params['imei']) && $params['imei']) {
            $whereParams['imei'] = $params['imei'];
        }

        $page = isset($params['page']) ? $params['page'] : null;

        $whereParams['status'] = [Imei::STATUS_OUT, Imei::STATUS_IN];

        $collect = ImeiRepository::list($whereParams, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return [];
        }

        return ['data'=>$items, 'limit'=>$limit, 'page'=>$page];
    }
}