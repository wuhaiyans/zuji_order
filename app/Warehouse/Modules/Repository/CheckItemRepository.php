<?php
/**
 * 检测单
 *
 * User: wangjinlin
 * Date: 2018/12/19
 * Time: 11:17
 */


namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Models\CheckItems;
use App\Warehouse\Models\ReceiveGoods;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class CheckItemRepository
{

    /**
     * 取消检测 针对设备
     */
    public static function getDetails($params)
    {
        $goods_model = ReceiveGoods::where([
            'receive_no'=>$params['receive_no'],
            'goods_no'=>$params['goods_no']
        ])->first();

        if (!$goods_model) {
            throw new NotFoundResourceException('goods设备未找到:' .$params['receive_no'] .'-'. $params['goods_no']);
        }
        $goods_row = $goods_model->toArray();

        $checkitems_model = CheckItems::where([
            'receive_no'=>$params['receive_no'],
            'goods_no'=>$params['goods_no']
        ])->first();

        if (!$checkitems_model) {
            throw new NotFoundResourceException('check设备未找到:' .$params['receive_no'] .'-'. $params['goods_no']);
        }
        $checkitems_row = $checkitems_model->toArray();

        $row = array_merge($goods_row,$checkitems_row);

        return $row;
    }

}