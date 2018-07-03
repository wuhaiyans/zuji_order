<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 13:49
 */

namespace App\Warehouse\Modules\Service;

use App\Warehouse\Config;
use App\Warehouse\Models\Receive;
use App\Warehouse\Models\ReceiveGoods;
use App\Warehouse\Models\ReceiveGoodsImei;
use App\Warehouse\Modules\Repository\ReceiveGoodsRepository;
use Illuminate\Support\Facades\Log;

class ReceiveGoodsService
{


    /**
     * @param $params
     * @return array
     * @throws \Exception
     *
     * 获取收货设备列表
     */
    public function list($params)
    {
        $limit = 20;
        if (isset($params['size']) && $params['size']) {
            $limit = $params['size'];
        }
        $whereParams = [];

        $search = $this->paramsSearch($params);
        if ($search) {
            $whereParams = array_merge($whereParams, $search);
        }


        //1：待配货；2：待发货；3：已发货，待用户签收；4：已签收完成；5：已拒签完成；6：已取消；
        if (isset($params['status']) && $params['status']) {
            $whereParams['status'] = $params['status'];
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $type = isset($params['type']) ? $params['type'] : 1;

        //组合时间查询
        $logic_params = [];
        if (isset($params['begin_time']) && $params['begin_time']) {
            array_push($logic_params, ['check_time', '>=', strtotime($params['begin_time'])]);
        }

        if (isset($params['end_time']) && $params['end_time']) {
            array_push($logic_params, ['check_time', '<=', strtotime($params['end_time'])]);
        }

        $collect = ReceiveGoodsRepository::list($whereParams, $logic_params, $limit, $page, $type);
        $items = $collect->items();

        if (!$items) {
            return ['data'=>[], 'per_page'=>$limit, 'total'=>0, 'current_page'=>0];
        }

        $result = $receiveNos = [];
        foreach ($items as $item) {
            $it = $item->toArray();
            array_push($receiveNos, $item->receive_no);

            $it['order_no'] = $item->receive->order_no;
            $it['receive_time'] = $item->receive->receive_time;
            $it['imei'] = '';
            $it['serial_number'] = '';

            //确认收货按钮
            $it['shouhuo']=($it['status']==ReceiveGoods::STATUS_INIT)?true:false;
            //确认同意换货操作
            $receive_row = $item->receive;
            if($it['status']==ReceiveGoods::STATUS_ALL_CHECK && $receive_row->type==Receive::TYPE_EXCHANGE){
                $it['huanhuo']=true;
            }else{
                $it['huanhuo']=false;
            }
            //当前状态
            $it['status']=ReceiveGoods::status($it['status']);
            //设备归还属性
            $it['type']=Receive::types($receive_row->type);
            //物流名称
            if($it['receive']['logistics_id']==0){
                $it['receive']['logistics_name'] = '无';
            }else{
                $it['receive']['logistics_name'] = Config::$logistics[$it['receive']['logistics_id']];
            }

            array_push($result, $it);
        }

        //查询imei
        $imeis = ReceiveGoodsImei::whereIn('receive_no', $receiveNos)->get()->toArray();

        if ($imeis) {//组合 imei
            foreach ($result as &$item) {
                foreach ($imeis as $imei) {
                    if ($imei['goods_no'] == $item['goods_no']) {
                        $item['imei'] = $imei['imei'];
                        $item['serial_number'] = $imei['serial_number'];
                    }
                }
            }unset($item);
        }

        return [
            'data'=>$result,
            'per_page'=>$limit,
            'total'=>$collect->total(),
            'current_page'=>$collect->currentPage(),
        ];

    }


    public static function searchKws()
    {
        $ks = [
            ReceiveGoodsRepository::SEARCH_TYPE_MOBILE    => '手机号',
            ReceiveGoodsRepository::SEARCH_TYPE_ORDER_NO  => '订单号',
            ReceiveGoodsRepository::SEARCH_TYPE_GOODS_NAME=> '设备名',
        ];

        return $ks;
    }


    /**
     * 查找字段整理
     * @param $params
     * @return array|bool
     */
    public function paramsSearch($params)
    {
        if (!isset($params['kw_type']) || !$params['kw_type']) {
            return false;
        }

        if (!isset($params['keywords']) || !$params['keywords']) {
            return false;
        }

        return [$params['kw_type'] => $params['keywords']];
    }



    /**
     * 各种状态的数量统计
     *
     * 默认取待检测的
     */
    public static function statistics($status = ReceiveGoods::STATUS_ALL_RECEIVE)
    {
        return ReceiveGoods::where(['status'=>$status])->count();
    }
}