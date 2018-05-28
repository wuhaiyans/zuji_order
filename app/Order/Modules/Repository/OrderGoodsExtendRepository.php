<?php
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use App\Lib\Goods\Goods;
use App\Order\Models\Order;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderLog;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Service\OrderInstalment;
use Illuminate\Support\Facades\DB;

class OrderGoodsExtendRepository
{

    public function __construct()
    {
    }
    public static function add($params){
        $data =[
            'order_no'=>$params['order_no'],
            'goods_id'=>$params['goods_id'],
            'goods_no'=>$params['goods_no'],
            'imei1'=>isset($params['imei1'])?$params['imei1']:"",
            'imei2'=>isset($params['imei2'])?$params['imei2']:"",
            'imei3'=>isset($params['imei3'])?$params['imei3']:"",
            'serial_number'=>$params['serial_number'],
            'status'=>0,
        ];
        $res =OrderLog::create($data);
        return $res->getQueueableId();
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