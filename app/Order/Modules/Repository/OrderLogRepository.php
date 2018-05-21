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

class OrderLogRepository
{
    const Type_User = 2;    //用户
    const Type_Admin = 1; //管理员
    const Type_System = 3; // 系统自动化任务
    const Type_Store =4;//线下门店

    protected $orderLog;


    public function __construct(OrderLog $orderLog)
    {
        $this->orderLog = $orderLog;
    }

    /**
     * 记录订单日志
     * @param $operator_id 操作员ID
     * @param $operator_name 操作员名字
     * @param $operator_type 操作员类型
     * @param $order_no
     * @param $action
     * @param $msg
     * @return mixed
     */
    public static function add($operatorId,$operatorName,$operatorType,$orderNo, $action, $msg ){
        $data =[
            'operator_id'=>$operatorId,
            'operator_name'=>$operatorName,
            'operator_type'=>$operatorType,
            'order_no'=>$orderNo,
            'action'=>$action,
            'msg'=>$msg,
            'system_time'=>time(),
        ];
        $log =OrderLog::create($data);
        return $log->getQueueableId();
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