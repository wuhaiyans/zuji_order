<?php
/**
 * 收货单仓库
 *
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:17
 */


namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Config;
use App\Warehouse\Models\CheckItems;
use App\Warehouse\Models\Receive;
use App\Warehouse\Models\ReceiveGoods;
use App\Warehouse\Models\ReceiveGoodsImei;
use App\Warehouse\Modules\Func\WarehouseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\IFTTTHandler;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class ReceiveRepository
{
    /**
     * 暂时弃用
     */
//    public static function generateReceiveNo()
//    {
//        return date('YmdHis') . rand(1000, 9999);
//    }

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
        $query = Receive::where($params);

        if (is_array($logic_params)) {
            foreach ($logic_params as $logic) {
                $query->where($logic[0], $logic[1] ,$logic[2]);
            }
        }
        return $query->paginate($limit,
            [
                'receive_no','order_no', 'logistics_id','logistics_no','customer','customer_mobile',
                'customer_address', 'status', 'type', 'create_time', 'receive_time','check_description',
                'status_time','check_time','check_result'
            ],
            'page', $page);
    }


    /**
     * @param $params
     * @return bool
     *
     * 修改物流
     */
    public static function logistics($params)
    {
        $receive = Receive::where(['receive_no'=>$params['receive_no']])->first();

        if (!$receive) {
            Log::error(__METHOD__ . '收货单未找到');
            throw new NotFoundResourceException('收货单未找到');
        }

        $receive->logistics_id = $params['logistics_id'];
        $receive->logistics_no = $params['logistics_no'];

        return $receive->update();
    }

    /**
     * 清单查询
     */
    public static function show($receive_no)
    {
        $model = Receive::find($receive_no);

        if (!$model) {
            throw new NotFoundResourceException('收货单' . $receive_no . '未找到');
        }

        $result = $model->toArray();
        $result['status_mark'] = $model->getStatus();

        if ($model->imeis) {
            $result['imeis'] = $model->imeis;
        }

        if ($model->goods) {
            $result['goods'] = $model->goods;
        }

        return $result;
    }

    /**
     * 创建
     */
    public static function create($data)
    {

        try {
            DB::beginTransaction();
            $receiveNo = WarehouseHelper::generateNo();
            $time = time();
            $da = [
                'receive_no' => $receiveNo,
                'app_id' => $data['app_id'],
                'order_no'  => $data['order_no'],
                'logistics_id' => isset($data['logistics_id']) ? $data['logistics_id'] : 0,
                'logistics_no' => isset($data['logistics_no']) ? $data['logistics_no'] : 0,
                'customer' => isset($data['customer']) ? $data['customer'] : 0,
                'customer_mobile' => isset($data['customer_mobile']) ? $data['customer_mobile'] : 0,
                'customer_address' => isset($data['customer_address']) ? $data['customer_address'] : 0,
                'status'    => Receive::STATUS_INIT,
                'create_time' => $time,
                'type' => isset($data['type']) ? $data['type'] : 0,
                'business_key' => $data['business_key']
            ];

            $model = new Receive();
            $model->create($da);

            $details = $data['receive_detail'];

            if (!is_array($details)) {
                throw new \Exception("缺少相关参数");
            }

            foreach ($details as $detail) {//存receiveGoods
                $detail['receive_no'] = $receiveNo;
                $detail['refund_no'] = isset($detail['refund_no']) ? $detail['refund_no'] : '';
                $detail['imei'] = isset($detail['imei']) ? $detail['imei'] : '';
                $detail['status'] = ReceiveGoodsImei::STATUS_WAIT_RECEIVE;
                $detail['create_time'] = $time;

                $gmodel = new ReceiveGoods();
                $gmodel->create($detail);

                if (!isset($detail['imei']) || !$detail['imei']) continue;
                //unset($detail['goods_name']);
                $mmodel = new ReceiveGoodsImei();
                $mmodel->create($detail);
            }

            DB::commit();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
            DB::rollBack();
        }

        return $receiveNo;
    }

    /**
     * 取消收货单
     */
    public static function cancel($receive_no)
    {
        $model = Receive::find($receive_no);

        if (!$model) {
            throw new NotFoundResourceException('收货单' . $receive_no . '未找到');
        }

        $model->status = Receive::STATUS_CANCEL;
        $model->status_time = time();
        return $model->update();

    }

    /**
     * 签收 收货单签收
     */
    public static function received($receive_no)
    {
        //收货单更新
        $model = Receive::find($receive_no);
        if (!$model) {
            throw new NotFoundResourceException('收货单' . $receive_no . '未找到');
        }

//        if ($model->status != Receive::STATUS_INIT) {
//            throw new \Exception('收货单' . $receive_no . '非待收货状态，收货失败');
//        }

        $goods = $model->goods;

        $status = Receive::STATUS_RECEIVED;
        $t = time();
        foreach ($goods as $g) {
//            if ($g->status == ReceiveGoods::STATUS_INIT || $g->status == ReceiveGoods::STATUS_PART_RECEIVE) {
//                $status = Receive::STATUS_INIT;
//            }
            $g->status = ReceiveGoods::STATUS_ALL_RECEIVE;
            $g->status_time = $t;
            $g->update();
        }

        $model->status = $status;
        $model->receive_time = $t;
        return $model->update();
    }


    /**
     * @param $params
     * @return bool
     *
     * 收货单商品明细更新
     */
    public static function receiveDetail($params)
    {
        $model = ReceiveGoods::where([
            'receive_no'=>$params['receive_no'],
            'goods_no'=>$params['goods_no']
        ])->first();

        if (!$model) {
            throw new NotFoundResourceException('收货单商品' . $params['goods_no'] . '未找到');
        }

        $model->quantity_received = $params['quantity'];

        if ($model->quantity > $params['quantity']) {
            $model->status = ReceiveGoods::STATUS_PART_RECEIVE;
        } else {
            $model->status = ReceiveGoods::STATUS_ALL_RECEIVE;
        }

        $model->status_time = time();

        return $model->update();
    }


    /**
     *
     *
     * 	'goods_no' => '',//商品编号<br/>
     *		'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
     *		'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
     *		'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
     *		'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
     */

    /**
     * 检测完成
     */
    public static function checkItemsFinish($receive_no)
    {
        $checkItems = CheckItems::where(['receive_no'=>$receive_no])->get();

        if (!$checkItems) return false;


        return $checkItems->toArray();

//        $result = [];
//
//        foreach ($checkItems as $item) {
//            $result[] = [
//                'goods_no' => $item->goods_no,
//                'check_item' => $item->check_item,//检测项
//                'check_name' => $item->check_name, //测试名
//                'check_description' => $item->check_description,//检测备注
//                'check_result' => $item->check_result,//检测结果
//                'check_price' => $item->check_price
//            ];
//        }



    }


    /**
     * 取消签收 为待收货状态
     */
    public static function cancelReceive($receive_no)
    {

        $model = Receive::find($receive_no);

        if (!$model) {
            throw new NotFoundResourceException('收货单' . $receive_no . '未找到');
        }

        if ($model->status != Receive::STATUS_RECEIVED) {
            throw new \Exception('收货单' . $receive_no . '非已收货状态，取消收货失败');
        }

        $model->status = Receive::STATUS_INIT;

        return $model->update();
    }


    /**
     * @param $receive_no
     * @param $serial_no
     * @param $data
     * @return bool
     * @throws \Exception
     *
     * 检测
     */
    public static function check($receive_no,$goods_no, $data)
    {

        try {
            DB::beginTransaction();

            $goods_model = ReceiveGoods::where(['receive_no'=>$receive_no, 'goods_no'=>$goods_no])->first();

            if (!$goods_model) {
                throw new NotFoundResourceException('设备未找到:' . $goods_no);
            }

            if (!$data['check_result']) {
                throw new \Exception('请选择检测结果');
            }



            if ($data['check_result'] == ReceiveGoods::CHECK_RESULT_FALSE && !$data['check_description']) {
                throw new \Exception('请选择检测不合格原因');
            }

            $goods_model->check_result = $data['check_result'];
            $goods_model->check_time = time();
            $goods_model->check_description = isset($data['check_description']) ? $data['check_description'] : '';
            $goods_model->check_price = isset($data['check_price']) ? $data['check_price'] : 0.00;
            $goods_model->status = ReceiveGoods::STATUS_ALL_CHECK;//检测完成
            $goods_model->save();

            $receiver = $goods_model->receive;
            $receiver->updateCheck();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        return true;
    }




    /**
     * 验收 针对设备 针对imei
     */
    public static function checkOld($receive_no, $imei, $data)
    {

        try {
            DB::beginTransaction();

            $mini = ReceiveGoodsImei::where(['receive_no'=>$receive_no, 'imei'=>$imei])->first();

            if (!$mini) {
                throw new NotFoundResourceException('设备未找到，imei:' . $imei);
            }

            if (!$data['check_result']) {
                throw new \Exception('请选择检测结果');
            }

            if ($data['check_result'] == ReceiveGoodsImei::RESULT_NOT && !$data['check_description']) {
                throw new \Exception('请选择检测不合格原因');
            }

            $mini->check_result = $data['check_result'];
            $mini->check_time = time();
            $mini->check_description = isset($data['check_description']) ? $data['check_description'] : '';
            $mini->check_price = isset($data['check_price']) ? $data['check_price'] : 0.00;
            $mini->status = ReceiveGoodsImei::STATUS_CHECK_OVER;//检测完成
            $mini->save();

            $receiver = $mini->receive;
            $receiver->updateCheck();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 取消检测 针对设备
     */
    public static function cancelCheck($params)
    {
        $goods_model = ReceiveGoods::where([
            'receive_no'=>$params['receive_no'],
            'goods_no'=>$params['goods_no']
        ])->first();

        if (!$goods_model) {
            throw new NotFoundResourceException('设备未找到:' . $params['goods_no']);
        }

        $goods_model->check_result = ReceiveGoods::CHECK_RESULT_INVALID;
        $goods_model->check_description = '';
        $goods_model->status = ReceiveGoods::STATUS_ALL_RECEIVE;
        $goods_model->check_price = 0;
        $goods_model->status_time = time();

        return $goods_model->update();
    }

    /**
     * 完成签收 针对收货单
     */
    public static function finishCheck($receive_no)
    {
        $receive = Receive::find($receive_no);

        if (!$receive) {
            throw new NotFoundResourceException('单号未找到:' . $receive);
        }

        $receive->status = Receive::STATUS_FINISH;//检测完成

        if ($receive->update()){
            return $receive;
        }

        return false;
    }

    /**
     * 录入检测单
     */
    public static function checkItem($params)
    {
        //$params['create_time'] = time();
        $params['check_result'] = isset($params['check_result']) ? $params['check_result'] : CheckItems::RESULT_FALSE;
        $params['check_description'] = isset($params['check_description']) ? $params['check_description'] : '无';
        $params['compensate_amount'] = isset($params['compensate_amount']) ? $params['compensate_amount'] : 0;

        $model = new CheckItems();
        return $model->create($params);
    }

    /**
     * 录入检测项
     */
    public static function checkItems($params)
    {
        $check_item = $params['check_item'];
        $items = Config::$check_items;
        $params['check_name'] = isset($items[$check_item]) ? $items[$check_item] : '';

        $params['create_time'] = time();
        $params['check_result'] = isset($params['check_result']) ? $params['check_result'] : CheckItems::RESULT_FALSE;

        $model = new CheckItems();
        return $model->create($params);
    }

}