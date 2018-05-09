<?php
/**
 *  发货数据处理
 *
 * User: wangjinlin
 * Date: 2018/5/7
 * Time: 16:32
 */
namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Models\DeliveryGoodsImei;
use App\Warehouse\Models\DeliveryLog;
use App\Warehouse\Models\Delivery;
use App\Warehouse\Models\DeliveryGoods;
use App\Warehouse\Modules\Inc\DeliveryStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class DeliveryRepository
{

    private $delivery;
    private $deliveryGoods;
    private $deliveryLog;

    public function __construct(Delivery $delivery, DeliveryGoods $deliveryGoods, DeliveryLog $deliveryLog)
    {
        $this->delivery = $delivery;
        $this->deliveryGoods = $deliveryGoods;
        $this->deliveryLog = $deliveryLog;
    }
    public function create($data){
        //创建发货单
        // 18位发货单号(YYYYMMDDHHIISS+4位随机数)
        $rand_no =rand(1000,9999);
        $delivery_row['delivery_no'] = date('YmdHis',time()).$rand_no;
        $delivery_row['order_no'] = $data['order_no'];
        $delivery_row['status'] = DeliveryStatus::DeliveryStatus1;
        $delivery_row['create_time'] = time();

        DB::beginTransaction();
        try {
            $this->delivery->save($delivery_row);

            //创建发货商品清单
            foreach ($data['delivery_detail'] as $k=>$val){
                $row = [
                    'delivery_no'   =>  $delivery_row['delivery_no'],
                    'serial_no'     =>  $data['serial_no'],
                    'sku_no'        =>  $val['sku_no'],
                    'quantity'      =>  $val['quantity'],
                    'status'        =>  DeliveryStatus::DeliveryGoodsStatus0,
                    'status_time'   =>  time()
                ];
                $this->deliveryGoods->save($row);
            }

            //发货单日志
            $log_row = [
                'delivery_no'   =>  $delivery_row['delivery_no'],
                'serial_no'     =>  0,
                'description'   =>  DeliveryStatus::getStatusName(DeliveryStatus::DeliveryStatus1),
                'create_time'   =>  time()
            ];
            $this->deliveryLog->save($log_row);

        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();die;
        }

        DB::commit();

    }



    /**
     * @param $order_no
     * 取消发货
     */
    public static function cancel($order_no)
    {
        $model = Delivery::where('order_no', $order_no)->first();
        if (!$model) {
            throw new NotFoundResourceException('订单号' . $order_no . '未找到');
        }
        $model->status = Delivery::STATUS_CANCEL;
        return $model->update();
    }



    /**
     * 收货
     */
    public static function receive($delivery_no, $auto=false)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }
        $model->status = Delivery::STATUS_RECEIVED;
        return $model->update();
    }


    public static function detail($delivery_no)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }

        $result = $model->toArray();

        if ($model->imeis) {
            $result['imeis'] = $model->imeis;
        }

        if ($model->goods) {
            $result['goods'] = $model->goods;
        }

        return $result;
    }


    public static function imeis($delivery_no)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }

        return $model->imeis;

    }

    /**
     * @param $order_no
     * 发货
     */
    public static function send($order_no)
    {
        $model = Delivery::where(['order_no'=> $order_no, 'status'=>Delivery::STATUS_WAIT_SEND])->first();

        if (!$model) {
            throw new NotFoundResourceException($order_no . '号待发货的订单未找到');
        }

        $model->status = Delivery::STATUS_SEND;
        $model->delivery_time = time();

        return $model->save();
    }


    /**
     * 修改物流
     */
    public static function logistics($delivery_no, $logistics_id, $logistics_no)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }

        $model->logistics_id = $logistics_id;
        $model->logistics_no = $logistics_no;

        return $model->save();
    }

    /**
     * @param $delivery_id
     * 取消配货
     */
    public static function cancelMatch($delivery_no)
    {

        try {
            DB::beginTransaction();

            $model = Delivery::findOrFail($delivery_no);

            $model->status = Delivery::STATUS_INIT;
            $model->save();
            DeliveryImeiRepository::cancelMatch($delivery_no);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }


    public static function list($params, $limit, $page=null)
    {
        return Delivery::where($params)->paginate($limit,
            [
                'delivery_no','order_no', 'logistics_id','logistics_no',
                'status', 'create_time', 'delivery_time', 'status_remark'
            ],
            'page', $page);
    }



    /**
     * @param $delivery_id
     * 配货完成
     */
    public static function finishMatch($delivery_no)
    {

    }









}