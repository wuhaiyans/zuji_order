<?php
/**
 *
 * @author: wansq
 * @since: 1.0
 * Date: 2018/6/7
 * Time: 11:28
 */

namespace App\Warehouse\Controllers\Api\v1;


/**
 * Class ReceiveGoodsController
 * @package App\Warehouse\Controllers\Api\v1
 *
 *
 */
class ReceiveGoodsController extends Controller
{
    /**
     * 列表查询
     *
     */
    public function list()
    {
        $params = $this->_dealParams([]);
        $list = $this->ReceiveGoodsService->list($params);
        return \apiResponse($list);
    }
}