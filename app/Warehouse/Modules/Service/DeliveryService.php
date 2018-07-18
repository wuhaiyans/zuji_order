<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 14:36
 */


namespace App\Warehouse\Modules\Service;

use App\Order\Modules\Inc\OrderStatus;
use App\Warehouse\Config;
use App\Warehouse\Models\Delivery;
use App\Warehouse\Models\DeliveryGoods;
use App\Warehouse\Modules\Func\WarehouseHelper;
use App\Warehouse\Modules\Repository\DeliveryRepository;
use Illuminate\Support\Facades\Log;

class DeliveryService
{

    const TIME_TYPE_CREATE = 'create';//创建时间
    const TIME_TYPE_DELIVERY = 'delivery'; //发货时间
    const TIME_TYPE_NONE = 'none'; //不限时间



    //查找类型
    const SEARCH_MOBILE = 'customer_mobile';//手机
    const SEARCH_ORDER_NO = 'order_no';//订单号
    const SEARCH_DELIVERY_NO = 'delivery_no';//订单号
    const SEARCH_LOGISTIC_NO = 'logistics_no';


    static $searchs = [
        self::SEARCH_MOBILE => 'customer_mobile',
        self::SEARCH_ORDER_NO => 'order_no',
        self::SEARCH_DELIVERY_NO => 'delivery_no',
        self::SEARCH_LOGISTIC_NO => 'logistics_no'
    ];



    public static function searchKws()
    {
        $ks = [
            self::SEARCH_MOBILE => '手机号',
            self::SEARCH_DELIVERY_NO => '发货单号',
            self::SEARCH_ORDER_NO => '订单号',
            self::SEARCH_LOGISTIC_NO => '物流单号'
        ];

        return $ks;
    }




    /**
     * @param $order_no
     * @throws \Exception
     * 取消
     */
    public function cancel($order_no)
    {
        if (!DeliveryRepository::cancel($order_no)) {
            throw new \Exception('取消发货失败');
        }
    }


    public function cancelDelivery($delivery_no)
    {
        if (!DeliveryRepository::cancelDelivery($delivery_no)) {
            throw new \Exception('取消发货失败');
        }
    }


    public function match($delivery_no)
    {
        if (!DeliveryRepository::match($delivery_no)) {
            throw new \Exception('配货操作失败');
        }
    }


    /**
     * @param $params
     * 配货
     * $params devivery_no, serial_no, quantity, imeis(imeis为数组格式，可能一设备对应多imei)
     */
    public function matchGoods($params)
    {
        if (!DeliveryRepository::matchGoods($params)) {
            throw new \Exception('配货失败,请重新操作');
        }
    }

	/**
	 * 取消商品的配货信息
	 * @param type $params
	 * [
	 *		'delivery_no'	=> '', //【必选】string 发货单
	 *		'goods_no'		=> '', //【必选】string 商品编号
	 * ]
	 * @throws \Exception
	 */
    public function cancelMatchGoods($params)
    {
        if (!DeliveryRepository::cancelMatchGoods($params)) {
            throw new \Exception('取消配货失败,请重新操作');
        }
    }


    /**
     * @param $delivery_no
     * @param bool $auto
     * @throws \Exception
     * 签收
     */
    public function receive($order_no, $receive_type=Delivery::RECEIVE_TYPE_USER)
    {

        $receive_info = DeliveryRepository::receive($order_no, $receive_type);

        if (!$receive_info) {
            throw new \Exception('签收失败');
        }

        return $receive_info;
    }

    /**
     * @param $delivery_no
     * @return array
     * @throws \Exception
     * 清单明细
     */
    public function detail($delivery_no)
    {
        if (!($detail = DeliveryRepository::detail($delivery_no))) {
            throw new \Exception('发货单' . $delivery_no . '不存在');
        }

        return $detail;
    }

    /**
     * @param $delivery_no
     * @return mixed
     * @throws \Exception
     * 清单imei
     */
    public function imeis($delivery_no)
    {
        if (!($imeis = DeliveryRepository::imeis($delivery_no))) {
            throw new \Exception('未找到imei');
        }

        return $imeis;
    }

    /**
     * @param $order_no
     * @throws \Exception
     * 发货操作
     */
    public function send($params)
    {
        if (!DeliveryRepository::send($params)) {
            throw new \Exception('发货操作失败');
        }
    }


    /**
     * @param $delivery_no
     * @param $logistics_id
     * @param $logistics_no
     * @throws \Exception
     * 修改物流
     */
    public function logistics($params)
    {
        if (!DeliveryRepository::logistics($params)) {
            throw new \Exception('修改物流失败');
        }
    }


    /**
     * @param $delivery_no
     * @throws \Exception
     * 取消配货
     */
    public function cancelMatch($delivery_no)
    {
        if (!DeliveryRepository::cancelMatch($delivery_no)) {
            throw new \Exception('取消配货失败');
        }
    }


    /**
     * @param null $logisticsId
     * @return string
     *
     * 获取快递名
     */
    public function getLogistics($logisticsId=null)
    {
        if ($logisticsId === null) {
            //return Config::$logistics;
            foreach (Config::$logistics as $k=>$item){
                $data[] = [
                    'id'=>$k,
                    'name'=>$item,
                ];
            }
            return $data;
        }
        return isset(Config::$logistics[$logisticsId]) ? Config::$logistics[$logisticsId] : '';
    }

    /**
     * @param $params
     * @return array
     * @throws \Exception
     *
     * 列表
     */
    public function lists($params)
    {
        $limit = 20;
        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];

        $logic_params = [];
        //1：待配货；2：待发货；3：已发货，待用户签收；4：已签收完成；5：已拒签完成；6：已取消；
        if (isset($params['status']) && $params['status']) {
            $whereParams['status'] = $params['status'];
        } else {
            array_push($logic_params, ['status', '>', Delivery::STATUS_NONE]);
        }

//        if (isset($params['order_no']) && $params['order_no']) {
//            $whereParams['order_no'] = $params['order_no'];
//        }
//        if (isset($params['delivery_no']) && $params['delivery_no']) {
//            $whereParams['delivery_no'] = $params['delivery_no'];
//        }

        $search = $this->paramsSearch($params);
        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $time_type   = isset($params['time_type']) ? $params['time_type'] : 'none';


        if ($time_type != 'none') {
            if (!isset($params['begin_time']) || !$params['begin_time']) {
                throw new \Exception('请填写开始时间');
            }

            if (!isset($params['end_time']) || !$params['end_time']) {
                throw new \Exception('请填写结束时间');
            }

            switch ($time_type) {
                case self::TIME_TYPE_CREATE:
                    array_push($logic_params, ['create_time', '<=', strtotime($params['end_time'].' 23:59:59')]);
                    array_push($logic_params, ['create_time', '>=', strtotime($params['begin_time'])]);
                    break;

                case self::TIME_TYPE_DELIVERY:
                default:
                    array_push($logic_params, ['delivery_time', '<=', strtotime($params['end_time'].' 23:59:59')]);
                    array_push($logic_params, ['delivery_time', '>=', strtotime($params['begin_time'])]);
            }
        }

        $collect = DeliveryRepository::lists($whereParams, $logic_params, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return ['data'=>[], 'per_page'=>$limit, 'total'=>0, 'current_page'=>0];
        }

        $show_detail = isset($params['detail']) ? $params['detail'] : false;

//        if (!$show_detail) {
//            return ['data'=>$items, 'per_page'=>$limit, 'total'=>$collect->total(), 'current_page'=>$collect->currentPage()];
//        }

        $result = [];
        foreach ($items as $item) {
            $it = $item->toArray();
            $it['logistics_name'] = WarehouseHelper::getLogisticsName($it['logistics_id']);;
            $it['status_mark'] = $item->getStatus();
            $it['create_time'] = date('Y-m-d H:i', $it['create_time']);
            $it['delivery_time'] = date('Y-m-d H:i', $it['delivery_time']);

            if ($show_detail) {
                $goods_list = $item->goods->toArray();
                $imei_list  = $item->imeis->toArray();

                foreach ($goods_list as &$g) {
                    if (!is_array($imei_list)) continue;
                    foreach ($imei_list as $im) {
                        if ($im['goods_no'] == $g['goods_no']) {

                            $g['imei'] = $im['imei'];
                            $g['price'] = $im['price'];
                        }
                    }
                }unset($g);

                $it['imeis'] = $item->imeis->toArray();
                $it['goods'] = $goods_list;
            }

            //确认收货
            if($item->status == Delivery::STATUS_SEND && $item->business_key == OrderStatus::BUSINESS_BARTER){
                $it['buttons']['confirm_receive'] = true;
            }else{
                $it['buttons']['confirm_receive'] = false;
            }

            $it['buttons']['match'] = $item->status == Delivery::STATUS_INIT ? true : false;//配货
            $it['buttons']['delivery'] = $item->status == Delivery::STATUS_WAIT_SEND ? true : false; //发货

            //取消发货(一期不做先注释)
            //if($item->status == Delivery::STATUS_INIT || Delivery::STATUS_WAIT_SEND){
            //    $it['buttons']['cancel_delivery'] = true;
            //}else{
            //    $it['buttons']['cancel_delivery'] = false;
            //}
            $it['buttons']['cancel_delivery'] = false;

            $it['buttons']['already_match'] = $item->status == Delivery::STATUS_WAIT_SEND ? true : false;//已配货

            array_push($result, $it);
        }

        $status_list = Delivery::sta();
        return [
            'data'=>$result,
            'per_page'=>$limit,
            'total'=>$collect->total(),
            'current_page'=>$collect->currentPage(),
            'status_list' => $status_list,
            'kw_types' => self::searchKws()
        ];

    }


    /**
     * @param $params
     * @return array
     * @throws \Exception
     *
     * 数据导出 与列表类似
     */
    public function export($params)
    {
        $limit = 20000;
//        if (isset($params['size']) && $params['size']) {
//            $limit = $params['size'];
//        }
        $whereParams = [];

        $logic_params = [];
        //1：待配货；2：待发货；3：已发货，待用户签收；4：已签收完成；5：已拒签完成；6：已取消；
        if (isset($params['status']) && $params['status']) {
            $whereParams['status'] = $params['status'];
        } else {
            array_push($logic_params, ['status', '>', Delivery::STATUS_NONE]);
        }

        $search = $this->paramsSearch($params);
        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $time_type   = isset($params['time_type']) ? $params['time_type'] : 'none';


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

                case self::TIME_TYPE_DELIVERY:
                default:
                    array_push($logic_params, ['delivery_time', '<=', strtotime($params['end_time'])]);
                    array_push($logic_params, ['delivery_time', '>=', strtotime($params['begin_time'])]);
            }
        }

        $collect = DeliveryRepository::lists($whereParams, $logic_params, $limit, $page);
        $items = $collect->items();

        if (!$items) {
            return false;
        }


        $result = [];
        foreach ($items as $item) {
            $it = $item->toArray();
            $it['logistics_name'] = WarehouseHelper::getLogisticsName($it['logistics_id']);;
            $it['status_mark'] = $item->getStatus();
            $it['create_time'] = date('Y-m-d H:i', $it['create_time']);
            $it['delivery_time'] = date('Y-m-d H:i', $it['delivery_time']);

            $goods_list = $item->goods->toArray();
            $imei_list  = $item->imeis->toArray();


            if (is_array($goods_list) && is_array($imei_list)) {
                foreach ($goods_list as &$g) {
                    foreach ($imei_list as $im) {
                        if ($im['goods_no'] == $g['goods_no']) {
                            $g['imei'] = $im['imei'];
                            $g['price'] = $im['price'];
                        }
                    }
                }unset($g);

            }
            $it['goods'] = $goods_list;
            array_push($result, $it);
        }

        return $result;

    }

    /**
     * @param $id
     * @param $no
     * 取物流名
     */
    public function getLogisticsName($id)
    {
        return '顺丰';
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


    public function getOrderNoByDeliveryNo($delivery_no)
    {
        return DeliveryRepository::getOrderNoByDeliveryNo($delivery_no);
    }


    /**
     * 各种状态的数量统计
     *
     * 默认取待发货的
     */
    public static function statistics($status = Delivery::STATUS_WAIT_SEND)
    {
        return Delivery::where(['status'=>$status])->count();
    }

}