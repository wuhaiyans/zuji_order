<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGoodsExtend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderGoodsExtendRepository
{

    public function __construct()
    {
    }
    public static function add($orderNo,$goodsInfo){
        foreach ($goodsInfo as $k=>$v){
            $data =[
                'order_no'=>$orderNo,
                'goods_no'=>$v['goods_no'],
                'imei1'=>isset($v['imei1'])?$v['imei1']:"",
                'imei2'=>isset($v['imei2'])?$v['imei2']:"",
                'imei3'=>isset($v['imei3'])?$v['imei3']:"",
                'serial_number'=>$v['serial_number'] ? $v['serial_number'] : '',
                'status'=>0,
            ];
            $res =OrderGoodsExtend::create($data);
            $id =$res->getQueueableId();
            if(!$id){
                return false;
            }
        }
        return true;
    }

    /**
     * heaven
     * 获取订单日志
     * @param array $param  orderNo 订单号
     * @return array|bool
     */
    public static function getOrderLog($param = array())
    {
        if (empty($param)) {
            return false;
        }
        if (isset($param['order_no']) && !empty($param['order_no']))
        {
            $orderData = DB::table('order_log')
                ->leftJoin('order_userinfo', function ($join) {
                    $join->on('order_info.order_no', '=', 'order_userinfo.order_no');
                })
                ->where('order_info.order_no', '=', $param['order_no'])
                ->select('order_info.*','order_userinfo.*')
                ->first();

            return !empty($orderData)?objectToArray($orderData):false;
        }
        return false;

    }


}