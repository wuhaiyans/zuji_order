<?php
/**
 *  发货数据处理
 *
 * User: wangjinlin
 * Date: 2018/5/7
 * Time: 16:32
 */
namespace App\Warehouse\Modules\Repository;

use App\Order\Models\DeliveryLog;
use App\Warehouse\Models\Delivery;
use App\Warehouse\Models\DeliveryGoods;
use App\Warehouse\Modules\Inc\DeliveryStatus;
use Illuminate\Support\Facades\DB;

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

}