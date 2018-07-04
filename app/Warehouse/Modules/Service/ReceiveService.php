<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:18
 */


namespace App\Warehouse\Modules\Service;

use App\Warehouse\Models\Receive;
use App\Warehouse\Modules\Func\WarehouseHelper;
use App\Warehouse\Modules\Repository\DeliveryRepository;
use App\Warehouse\Modules\Repository\ImeiRepository;
use App\Warehouse\Modules\Repository\ReceiveRepository;
use PHPUnit\Framework\MockObject\Stub\Exception;

class ReceiveService
{

    const TIME_TYPE_CREATE = 'create';//创建时间
    const TIME_TYPE_RECEIVE = 'receive'; //发货时间
    const TIME_TYPE_NONE = 'none'; //不限时间

    /**
     * @param $params
     * @param $limit
     * @param $page
     * @return array
     *
     * 列表
     */
    public function list($params)
    {
        $limit = 20;
        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];

        //1：待收货；2：已收货，待拆包；3：检测完成；
        if (isset($params['status']) && $params['status']) {
            $whereParams['status'] = $params['status'];
        }

        $search = $this->paramsSearch($params);
        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $time_type   = isset($params['time_type']) ? $params['time_type'] : 'none';

        $logic_params = [];
        if ($time_type != 'none') {
            if (!isset($params['begin_time']) || !$params['begin_time']) {
                throw new \Exception('请填写开始时间');
            }

            if (!isset($params['end_time']) || !$params['end_time']) {
                throw new \Exception('请填写结束时间');
            }

            switch ($time_type) {
                case self::TIME_TYPE_CREATE:
                    array_push($logic_params, ['create_time', '<=', strtotime($params['end_time'])]);
                    array_push($logic_params, ['create_time', '>=', strtotime($params['begin_time'])]);
                    break;

                case self::TIME_TYPE_RECEIVE:
                default:
                    array_push($logic_params, ['receive_time', '<=', strtotime($params['end_time'])]);
                    array_push($logic_params, ['receive_time', '>=', strtotime($params['begin_time'])]);
            }
        }


        $collect = ReceiveRepository::list($whereParams, $logic_params, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return ['data'=>[], 'per_page'=>$limit, 'total'=>0, 'current_page'=>0];
        }

        $show_detail = isset($params['detail']) ? $params['detail'] : false;

        if (!$show_detail) {
            return ['data'=>$items, 'per_page'=>$limit, 'total'=>$collect->total(), 'current_page'=>$collect->currentPage()];
        }

        $result = [];
        foreach ($items as $item) {
            $it = $item->toArray();
            $it['logistics_name'] = WarehouseHelper::getLogisticsName($it['logistics_id']);
            $it['status_mark'] = $item->getStatus();
            $it['create_time'] = date('Y-m-d H:i', $it['create_time']);
            $it['receive_time'] = date('Y-m-d H:i', $it['receive_time']);

            $goods_list = $item->goods->toArray();
            $imei_list  = $item->imeis->toArray();

            foreach ($goods_list as &$g) {
                if (!is_array($imei_list)) continue;
                foreach ($imei_list as $im) {
                    if ($im['goods_no'] == $g['goods_no']) {
                        $g['imeis'][] = $im['imei'];
                    }
                }
            }unset($g);

            $it['imeis'] = $item->imeis->toArray();
            $it['goods'] = $goods_list;

            array_push($result, $it);
        }

        return ['data'=>$result, 'per_page'=>$limit, 'total'=>$collect->total(), 'current_page'=>$collect->currentPage()];


    }


    /**
     * @param $id
     * @param $no
     * 取物流名
     */
    public function getLogisticsName($id)
    {
        return '顺风';
    }

    /**
     * 查找类型
     */
    public function paramsSearch($params)
    {
        if (!isset($params['kw_type']) || !$params['kw_type']) {
            return false;
        }

        if (!isset($params['keywords']) || !$params['keywords']) {
            return false;
        }

        return [self::$searchs[$params['kw_type']] => $params['keywords']];
    }



    /**
     * 创建
     */
    public function create($data)
    {
        $receiveNo = ReceiveRepository::create($data);
        if (!$data) {
            throw new \Exception('收货单创建失败');
        }
        return $receiveNo;
    }

    /**
     * @param $receive_no
     * @throws \Exception
     * 取消收货
     */
    public function cancel($receive_no)
    {
        if (!ReceiveRepository::cancel($receive_no)) {
            throw new \Exception('取消收货单失败');
        }
    }

    /**
     * @param $receive_no
     * @throws \Exception
     * 收发货签收
     */
    public function received($receive_no)
    {
        if (!ReceiveRepository::received($receive_no)) {
            throw new \Exception($receive_no . '号收货单签收失败');
        }
        //IMEI入库
//        if (!ImeiRepository::received($receive_no)) {
//            throw new \Exception($receive_no . '号收货单签收失败');
//        }
    }

    /**
     * @param $params
     * @throws \Exception
     * 订单签收
     */
    public function receiveDetail($params)
    {
        if (!ReceiveRepository::receiveDetail($params)) {
            throw new \Exception($params['receive_no'] . '号收货单商品签收失败');
        }
        //IMEI入库
    }

    /**
     * @param $receive_no
     * 取消签收
     */
    public function cancelReceive($receive_no)
    {
        if (!ReceiveRepository::cancelReceive($receive_no)) {
            throw new \Exception($receive_no . '取消签收失败');
        }
    }

    /**
     * 检测
     */
    public function check($receive_no, $goods_no, $data)
    {
        if (!ReceiveRepository::check($receive_no, $goods_no, $data)) {
            throw new \Exception($receive_no . '设备:'.$goods_no.'验签失败');
        }
    }

    /**
     * 检测完成
     */
    public function checkItemsFinish($receive_no)
    {
        $items = ReceiveRepository::checkItemsFinish($receive_no);
        if (!$items) {
            throw new \Exception($receive_no . '操作失败');
        }
        return $items;
    }

    /**
     * @param $params
     * @throws \Exception
     *
     * 修改收货单物流信息
     */
    public function logistics($params)
    {
        if (!ReceiveRepository::logistics($params)) {
            throw new \Exception('发货单'.$params['receive_no'] .'修改物流失败');
        }
    }

    /**
     * 取消检测
     */
    public function cancelCheck($params)
    {
        if (!ReceiveRepository::cancelCheck($params)) {
            throw new \Exception($params['receive_no'] . '设备:'.$params['serial_no'].'取消签收失败');
        }
    }

    /**
     * 检测完成
     */
    public function finishCheck($receive_no)
    {
        $receive = ReceiveRepository::finishCheck($receive_no);
        if (!$receive) {
            throw new \Exception($receive_no . '检测完成操作失败');
        }
        return $receive;
    }

    /**
     * @param $receive_no
     * @return array
     * @throws \Exception
     * 清单
     */
    public function show($receive_no)
    {
        $detail = ReceiveRepository::show($receive_no);

        if (!$detail) {
            throw new \Exception('收货单' . $receive_no . '不存在');
        }

        return $detail;
    }

    /**
     * @param $params
     * 检测单
     */
    public function checkItem($params)
    {
        //创建检测单
        if (!ReceiveRepository::checkItem($params)) {
            throw new \Exception( '检测单:'.$params['receive_no'].'添加失败');
        }
        //修改收货单检测状态
        if (!ReceiveRepository::checkReceive($params)) {
            throw new \Exception( '收货单'.$params['receive_no'].'检测修改失败');
        }
        //修改收货清单检测状态
        if (!ReceiveRepository::checkReceiveGoods($params)) {
            throw new \Exception( '收货清单:'.$params['receive_no'].'检测修改失败');
        }
    }

    /**
     * @param $params
     * 检测项
     */
    public function checkItems($params)
    {
        if (!ReceiveRepository::checkItems($params)) {
            throw new \Exception($params['receive_no'] . '设备:'.$params['goods_no'].'添加检测项失败');
        }
    }

    /**
     * 各种状态的数量统计
     *
     * 默认取待收货的
     */
    public static function statistics($status = Receive::STATUS_INIT)
    {
        return Receive::where(['status'=>$status])->count();
    }

    /**
     * 确认同意换货
     *      创建发货单
     */
    public function createDelivery($receive_no){
        $model = Receive::find($receive_no);
        $goods = $model->goods;
        if ($model->type==Receive::TYPE_EXCHANGE && $model->status==Receive::STATUS_FINISH && $model->check_result==Receive::CHECK_RESULT_OK ){
            $delivery = new DeliveryRepository();
            $data = [
                'order_no'=>$model->order_no,
                'app_id'=>$model->app_id,
                'customer'=>$model->customer,
                'customer_mobile'=>$model->customer_mobile,
                'customer_address'=>$model->customer_address,
                'business_key'=>$model->business_key,
                'business_no'=>0,
            ];
            foreach ($goods as $k=>$item){
                $data['delivery_detail'][]=[
                    'goods_name'=>$item->goods_name,
                    'goods_no'=>$item->goods_no,
                    'quantity'=>$item->quantity,
                ];
            }
            //创建发货单
            if (!$delivery->create($data)) {
                throw new \Exception('创建发货单失败');
            }
        }else{
            throw new \Exception('当前状态无法确认同意换货');
        }
    }

}