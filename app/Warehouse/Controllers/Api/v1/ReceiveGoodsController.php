<?php
/**
 *
 * @author: wansq
 * @since: 1.0
 * Date: 2018/6/7
 * Time: 11:28
 */

namespace App\Warehouse\Controllers\Api\v1;
use App\Warehouse\Models\ReceiveGoods;
use App\Warehouse\Modules\Service\ReceiveGoodsService;
use App\Warehouse\Config;

/**
 * Class ReceiveGoodsController
 * @package App\Warehouse\Controllers\Api\v1
 *
 *
 */
class ReceiveGoodsController extends Controller
{


    protected $goods;

    public function __construct(ReceiveGoodsService $receiveGoods)
    {
        $this->goods = $receiveGoods;
    }

    /**
     * 列表查询
     *
     */
    public function list()
    {
        $params = $this->_dealParams([]);
        $list = $this->goods->list($params);
        return \apiResponse($list);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * 公共参数
     */
    public function publics()
    {
        $data = [
            'kw_types'    => ReceiveGoodsService::searchKws()
        ];

        return apiResponse($data);
    }


    /**
     * 获取检测项
     */
    public function checkItems()
    {
        return Config::$check_items;
    }
}