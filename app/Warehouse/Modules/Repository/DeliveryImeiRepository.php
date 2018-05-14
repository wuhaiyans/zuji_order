<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 15:41
 */

namespace App\Warehouse\Modules\Repository;
use App\Warehouse\Models\DeliveryGoodsImei;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class DeliveryImeiRepository
{

    /**
     * 取消配货时，删除
     */
    public static function cancelMatch($delivery_no)
    {

        return DeliveryGoodsImei::where(['delivery_no'=>$delivery_no])->delete();

    }



    /**
     * @param $delivery_id
     * @param $imei
     * 添加
     */
    public static function batchAdd($delivery_no, $imeis)
    {
        $time = time();

        if (!is_array($imeis)) {
            throw new \Exception('参数错误');
        }

        foreach ($imeis as $imei) {

            if (!$imei['imei'] || !$imei['serial_no']) continue;

            DeliveryGoodsImei::find()->where(['delivery_no'=>$delivery_no])->delete();//先把所有的删除

            $model = new DeliveryGoodsImei();
            $model->delivery_no = $delivery_no;
            $model->imei = $imei['imei'];
            $model->status = DeliveryGoodsImei::STATUS_YES;
            $model->status_time = $time;
            $model->serial_no = $imei['serial_no'];
            $model->create_time = time();
            $model->save();
        }

        return true;
    }


    /**
     * @param $delivery_no
     * @param $imei
     * @param $serial_no
     * @return bool
     * 添加或修改
     */
    public static function add($delivery_no, $imei, $serial_no)
    {
        $model = DeliveryGoodsImei::where(['delivery_no'=>$delivery_no, 'serial_no'=>$serial_no])->first();

        if (!$model) {
            $model = new DeliveryGoodsImei();
            $model->delivery_no = $delivery_no;
            $model->serial_no = $serial_no;
            $model->status_time = time();
            $model->create_time = time();
        }
        $model->imei = $imei;
        $model->status = DeliveryGoodsImei::STATUS_YES;
        return $model->save();
    }


    /**
     * @param $delivery_no
     * @param $imei
     * @return bool|null
     * @throws \Exception
     * 删除
     */
    public static function del($delivery_no, $imei)
    {
        $model = DeliveryGoodsImei::where(['delivery_no'=>$delivery_no, 'imei'=>$imei])->first();
        if (!$model) {
            throw new NotFoundResourceException('对应imei未找到');
        }
        return $model->delete();
    }

}