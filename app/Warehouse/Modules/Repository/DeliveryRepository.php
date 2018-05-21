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
use App\Warehouse\Models\Imei;
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
        $delivery_row['app_id'] = $data['app_id'];

        DB::beginTransaction();
        try {
            $model = new Delivery();
            $model->create($delivery_row);

            //创建发货商品清单
            foreach ($data['delivery_detail'] as $k=>$val){
                $row = [
                    'delivery_no'   =>  $delivery_row['delivery_no'],
                    'serial_no'     =>  $val['serial_no'],
                    'quantity'      =>  $val['quantity'],
                    'status'        =>  DeliveryStatus::DeliveryGoodsStatus1,
                    'status_time'   =>  time()
                ];

                $goodsModel = new DeliveryGoods();
                $goodsModel->create($row);

            }
            //发货单日志
            $log_row = [
                'delivery_no'   =>  $delivery_row['delivery_no'],
                'serial_no'     =>  0,
                'description'   =>  DeliveryStatus::getStatusName(DeliveryStatus::DeliveryStatus1),
                'create_time'   =>  time()
            ];

            $logModel = new DeliveryLog();
            $logModel->create($log_row);
            DB::commit();
        } catch (\Exception $exc) {
            DB::rollBack();
            throw new \Exception($exc->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param $order_no
     * 订单端取消发货
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
     * @param $order_no
     * 前台取消发货
     */
    public static function cancelDelivery($delivery_no)
    {
        $model = Delivery::where('delivery_no', $delivery_no)->first();
        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }
        $model->status = Delivery::STATUS_CANCEL;
        return $model->update();
    }



    public static function match($delivery_no)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }

        $goods = $model->goods;

        foreach ($goods as $g){
            if ($g->status != DeliveryGoods::STATUS_ALL) {
                throw new \Exception('商品尚未全部配货完成');
            }
        }

        $model->status = Delivery::STATUS_WAIT_SEND;
        return $model->update();
    }

    /**
     * @param $params
     * @return bool
     * @throws \Exception
     * 单商品配货
     */
    public static function matchGoods($params)
    {
        try {
            DB::beginTransaction();

            $delivery_no = $params['delivery_no'];
            $serial_no   = $params['serial_no'];


            $delivery = Delivery::find($delivery_no);

            if (!$delivery) {
                throw new NotFoundResourceException('发货单不存在');
            }

            $time = time();
            $imei_data = [
                'delivery_no' => $delivery_no,
                'serial_no'   => $serial_no,
                'status'      => DeliveryGoodsImei::STATUS_YES,
                'create_time' => $time
            ];

            #1修改delivery_imei表
            if (isset($params['imeis']) && $params['imeis']) {
                $imeis = $params['imeis'];
                foreach ($imeis as $imei) {
                    if (!$imei) continue;
                    $goods_imei_model = DeliveryGoodsImei::where([
                        'delivery_no'=>$delivery_no,
                        'serial_no'=>$serial_no,
                        'imei'=>$imei
                    ])->first();

                    if ($goods_imei_model) continue;

                    #goods_imei表添加
                    $imei_data['imei'] = $imei;
                    $model = new DeliveryGoodsImei();
                    $model->create($imei_data);

                    #imei总表修改状态
                    $imei_model = Imei::find($imei);
                    $imei_model->status = Imei::STATUS_OUT;
                    $imei_model->update_time = $time;
                    $imei_model->update();
                }
            }

            #2修改 goods 状态
            $goods_model = DeliveryGoods::where([
                'delivery_no'=>$params['delivery_no'],
                'serial_no'=>$params['serial_no'
                ]])
                ->first();

            $goods_status = DeliveryGoods::STATUS_ALL;
            if ($goods_model->quantity > $params['quantity']) {
                $goods_status = DeliveryGoods::STATUS_PART;
            }

            $goods_data = [
                'quantity_delivered' => $params['quantity'],
                'status'             => $goods_status,
                'status_time'        => $time
            ];
            $goods_model->update($goods_data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception($e->getMessage());
        }

        return true;
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
        $model->status_time = time();
        $model->is_auto = (int)$auto;
        return $model->update();
    }


    /**
     * @param $delivery_no
     * @return array
     * 明细清单
     */
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


    /**
     * @param $delivery_no
     * @return mixed
     * 根据$delivery_no取imeis
     */
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
    public static function send($delivery_no)
    {
        $model = Delivery::where(['delivery_no'=> $delivery_no, 'status'=>Delivery::STATUS_WAIT_SEND])->first();

        if (!$model) {
            throw new NotFoundResourceException($delivery_no . '号待发货单未找到');
        }

        $model->status = Delivery::STATUS_SEND;
        $model->delivery_time = $model->status_time = time();

        return $model->save();
    }


    /**
     * 修改物流
     */
    public static function logistics($params)
    {

        $model = Delivery::find($params['delivery_no']);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $params['delivery_no'] . '未找到');
        }

        $model->logistics_id = $params['logistics_id'];
        $model->logistics_no =  $params['logistics_no'];
        $model->status_remark =  $model->status_remark .';物流备注'. $params['logistics_note'];

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


    /**
     * @param $params
     * @param $limit
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * 列表
     */
    public static function list($params, $logic_params, $limit, $page=null)
    {
        $query = Delivery::where($params);

        if (is_array($logic_params) && count($logic_params)>0) {
            foreach ($logic_params as $logic) {
                $query->where($logic[0], $logic[1] ,$logic[2]);
            }
        }
        return $query->paginate($limit,
            [
                'delivery_no','order_no', 'logistics_id','logistics_no','customer','customer_mobile',
                'customer_address','status', 'create_time', 'delivery_time', 'status_remark'
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


    public static function getOrderNoByDeliveryNo($delivery_no)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }

        return $model->order_no;
    }



}