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
use App\Warehouse\Models\Receive;
use App\Warehouse\Models\ReceiveGoods;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class CheckItemRepository
{

    /**
     * 线下门店根据订单号和商品编号查询检测详情
     */
    public static function getDetails($params)
    {
        $receive_model = Receive::where(['order_no'=>$params['order_no']])->orderBy('receive_no','desc')->first();
        if (!$receive_model) {
            throw new NotFoundResourceException('收货单order_no未找到:' . $params['order_no']);
        }
        $receive_row = $receive_model->toArray();

        $goods_model = ReceiveGoods::where([
            'receive_no'=>$receive_row['receive_no'],
            'goods_no'=>$params['goods_no']
        ])->first();

        if (!$goods_model) {
            throw new NotFoundResourceException('goods设备未找到:' .$receive_row['receive_no'] .'-'. $params['goods_no']);
        }
        $goods_row = $goods_model->toArray();

        $checkitems_model = CheckItems::where([
            'receive_no'=>$receive_row['receive_no'],
            'goods_no'=>$params['goods_no']
        ])->first();

        if (!$checkitems_model) {
            throw new NotFoundResourceException('check设备未找到:' .$receive_row['receive_no'] .'-'. $params['goods_no']);
        }
        $checkitems_row = $checkitems_model->toArray();

        $row = array_merge($goods_row,$checkitems_row);

        return $row;
    }

}