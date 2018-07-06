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
use App\Warehouse\Modules\Func\WarehouseHelper;
use App\Warehouse\Modules\Inc\DeliveryStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * Class DeliveryRepository
 * @package App\Warehouse\Modules\Repository
 *
 * 发货单仓库层 重数据 轻逻辑
 */
class DeliveryRepository
{

//    private $delivery;
//    private $deliveryGoods;
//    private $deliveryLog;
//
//    public function __construct(Delivery $delivery, DeliveryGoods $deliveryGoods, DeliveryLog $deliveryLog)
//    {
//        $this->delivery = $delivery;
//        $this->deliveryGoods = $deliveryGoods;
//        $this->deliveryLog = $deliveryLog;
//    }


	/**
	 * 创建发货单
	 * @param type $data
	 * @return boolean
	 * @throws \Exception
	 */
    public static function create($data)
    {
		// 发货单号
        $delivery_no = create_delivery_no();
        $time = time();

        $dRow = [
            'delivery_no' => $delivery_no,
            'order_no' => $data['order_no'],
            'status' => Delivery::STATUS_INIT,
            'create_time' => $time,
            'app_id' => $data['app_id'],
            'customer' => isset($data['customer']) ? $data['customer'] : '',
            'customer_mobile' => isset($data['customer_mobile']) ? $data['customer_mobile'] : '',
            'customer_address' => isset($data['customer_address']) ? $data['customer_address'] : '',
            'business_key' => $data['business_key'],
            'business_no' => $data['business_no']
        ];

        try {
            DB::beginTransaction();
            $model = new Delivery();
            $model->create($dRow);

            self::storeGoods($delivery_no, $data['delivery_detail']);
            self::storeLog($delivery_no);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception($e->getMessage());

            return false;
        }

        return true;
    }


    /**
     * @param $delivery_no
     * @param $data
     *
     * 存设备
     */
    public function storeGoods($delivery_no, $data)
    {
        $time = time();
        foreach ($data as $k=>$val){
            $row = [
                'delivery_no'   =>  $delivery_no,
//                'serial_no'     =>  $val['serial_no'],
                'goods_name'    => isset($val['goods_name']) ? $val['goods_name'] : '',
                'goods_no'      =>  $val['goods_no'],
                'quantity'      =>  isset($val['quantity']) ? $val['quantity'] : 1,
                'status'        =>  DeliveryGoods::STATUS_INIT,
                'status_time'   =>  $time
            ];
            $goodsModel = new DeliveryGoods();
            $goodsModel->create($row);
        }
    }

    /**
     * @param $delivery_no
     * @param $data
     *
     * 存日志
     */
    public function storeLog($delivery_no)
    {
        $log_row = [
            'delivery_no'   =>  $delivery_no,
            'serial_no'     =>  0,
            'description'   =>  DeliveryStatus::getStatusName(DeliveryStatus::DeliveryStatus1),
            'create_time'   =>  time()
        ];
        $logModel = new DeliveryLog();
        $logModel->create($log_row);
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

        //修改IMEI状态为库存中
        if($model->imeis){
            foreach ($model->imeis as $key=>$item){
                Imei::in($item->imei);
            }
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

        //修改IMEI状态为库存中
        if($model->imeis){
            foreach ($model->imeis as $key=>$item){
                Imei::in($item->imei);
            }
        }

        $model->status = Delivery::STATUS_CANCEL;
        return $model->update();
    }


    /**
     * @param $delivery_no string 发货单号
     * @return bool
     * @throws \Exception
     *
     * 这里判断其下设备，是否都已经配货完成，
     * 如果全部完成，则整单配货完成，否则状态为待配货
     */
    public static function match($delivery_no)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }
        $goods = $model->goods;

        $status = Delivery::STATUS_WAIT_SEND;//配货完成状态
        foreach ($goods as $g){
            if ($g->status != DeliveryGoods::STATUS_ALL) {
//                throw new \Exception('商品尚未全部配货完成');
                $status = Delivery::STATUS_INIT;
            }
        }
        $model->status = $status;
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
            $goods_no   = $params['goods_no'];


            $delivery = Delivery::find($delivery_no);

            if (!$delivery) {
                throw new NotFoundResourceException('发货单不存在');
            }

            $time = time();
            $imei_data = [
                'delivery_no' => $delivery_no,
                'goods_no'   => $goods_no,
                'status'      => DeliveryGoodsImei::STATUS_YES,
                'create_time' => $time
            ];

            #1修改delivery_imei表
            if (isset($params['imei']) && $params['imei']) {
                $goods_imei_model = DeliveryGoodsImei::where([
                    'delivery_no'=>$delivery_no,
                    'goods_no'=>$goods_no,
                    'imei'=>$params['imei']
                ])->first();

                if (!$goods_imei_model) {
                    #goods_imei表添加
                    $imei_data['imei'] = $params['imei'];
                    $model = new DeliveryGoodsImei();
                    $model->create($imei_data);
                }

                Imei::out($params['imei']);


//                $imeis = $params['imeis'];
//                foreach ($imeis as $imei) {
//                    if (!$imei) continue;
//                    $goods_imei_model = DeliveryGoodsImei::where([
//                        'delivery_no'=>$delivery_no,
//                        'goods_no'=>$goods_no,
//                        'imei'=>$imei
//                    ])->first();
//
//                    if ($goods_imei_model) continue;
//
//                    #goods_imei表添加
//                    $imei_data['imei'] = $imei;
//                    $model = new DeliveryGoodsImei();
//                    $model->create($imei_data);
//
//                    #imei总表修改状态
//                    Imei::out($imei);
//                }
            }

            #2修改 goods 状态
            $goods_model = DeliveryGoods::where([
                'delivery_no'=>$params['delivery_no'],
                'goods_no'=>$params['goods_no']
            ])->first();

            $goods_status = DeliveryGoods::STATUS_ALL;
            if ($goods_model->quantity > $params['quantity']) {
                $goods_status = DeliveryGoods::STATUS_PART;
            }

            $goods_data = [
                'quantity_delivered' => isset($params['quantity']) ? $params['quantity'] : $goods_model->quantity,
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
     * @param $order_no
     * @param bool $auto
     * @return bool
     *
     * 收货操作
     */
    public static function receive($order_no, $receive_type=Delivery::RECEIVE_TYPE_USER)
    {
        //$model = Delivery::find($order_no);
        $model = Delivery::where([
            ['order_no','=',$order_no],
            ['status','=',Delivery::STATUS_SEND]
        ])->first();

        if (!$model) {
            throw new NotFoundResourceException('发货单订单编号:' . $order_no . ',status:'.Delivery::STATUS_SEND.' 未找到');
        }
        $model->status = Delivery::STATUS_RECEIVED;
        $model->status_time = time();
        $model->receive_type = (int)$receive_type;

        $model->update();

        return $model->toArray();
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
        $goods = $model->goods->toArray();

        if ($model->imeis) {
            $result['imeis'] = $model->imeis;



            foreach ($goods as &$g) {
                foreach ($model->imeis as $i) {
                    if ($i->goods_no == $g['goods_no']) {
                        $g['imei'] = $i->imei;
                        $g['price'] = $i->price;
                        $g['apple_serial'] = $i->apple_serial;
                    }
                }
            }unset($g);

        }

        $result['goods'] = $goods;


//        if ($model->goods) {
//            $result['goods'] = $model->goods;
//        }

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
	 * 取消商品的配货信息
	 * @param type $params
	 * [
	 *		'delivery_no'	=> '', //【必选】string 发货单
	 *		'goods_no'		=> '', //【必选】string 商品编号
	 * ]
	 * @return boolean
	 */
    public static function cancelMatchGoods($params)
    {
        try {
			// 查询发货的商品信息
			$where = [
				'delivery_no'	=> $params['delivery_no'],
				'goods_no'		=> $params['goods_no']
			];
			$goodsModel = DeliveryGoods::where($where)->first();
			if( !$goodsModel ){
				\App\Lib\Common\LogApi::type('data-error')::error('商品配货取消失败', $where);
				return false;
			}
			// 重复操作
			if( $goodsModel->status == DeliveryGoods::STATUS_INIT ){
				return true;
			}
            $goodsModel->status = DeliveryGoods::STATUS_INIT;
            $goodsModel->status_time = time();
            $goodsModel->update();
			
			// 查询商品imei
			$imei_where = [
                'delivery_no'	=> $params['delivery_no'],
                'goods_no'		=> $params['goods_no'],
                'status'		=> DeliveryGoodsImei::STATUS_YES,
            ];
            $goodsImeiModel = DeliveryGoodsImei::where($imei_where)->first();
			if( !$goodsImeiModel ){
				\App\Lib\Common\LogApi::type('data-error')::error('商品配货imei取消失败', $imei_where);
				return false;
			}
			$goodsImeiModel->status = DeliveryGoodsImei::STATUS_NO;
			$goodsImeiModel->status_time = time();
			$b = $goodsImeiModel->update();
			if( !$b ){
				\App\Lib\Common\LogApi::type('data-save')::error('商品配货imei取消失败', $imei_where);
				return false;
			}
			// 还原 imei 状态
            Imei::in( $goodsImeiModel->imei );
			
        } catch (\Exception $e) {
			\App\Lib\Common\LogApi::error('商品取消配货失败', $e);
            Log::error($e->getMessage());
            return false;
        }

        return true;
    }


    /**
     * @param $order_no
     * 发货
     */
    public static function send($params)
    {
        $model = Delivery::where(['delivery_no'=> $params['delivery_no']])->first();

        if (!$model) {
            throw new NotFoundResourceException($params['delivery_no'] . '号待发货单未找到');
        }

        $model->logistics_id = $params['logistics_id'];
        $model->logistics_no =  $params['logistics_no'];
        $model->status_remark =  $model->status_remark .';物流备注'. $params['logistics_note'];

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
     *
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
    public static function lists($params, $logic_params, $limit, $page=null)
    {
        $query = Delivery::where($params)->orderByDesc('delivery_no');

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
     * @param $delivery_no
     * @return mixed
     *
     * 根据delivery_no查找order_no
     */
    public static function getOrderNoByDeliveryNo($delivery_no)
    {
        $model = Delivery::find($delivery_no);

        if (!$model) {
            throw new NotFoundResourceException('发货单' . $delivery_no . '未找到');
        }

        return $model->order_no;
    }



}