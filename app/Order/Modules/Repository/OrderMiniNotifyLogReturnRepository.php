<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderMiniNotifyLog;
use App\Order\Models\OrderMiniNotifyLogReturn;

/**
 * 小程序 请求回调 记录表(转发表)
 * Class OrderMiniRepository
 * Author zhangjinhui
 * @package App\Order\Modules\Repository
 */
class OrderMiniNotifyLogReturnRepository
{
    public function __construct(){}

    /**
     * 添加芝麻订单信息
     * @param $data
     * @return $last_id
     */
    public static function add($data){
        $info =OrderMiniNotifyLogReturn::create($data);
        return $info->getQueueableId();
    }


    /**
     * 判断是否调用修改订单数据
     * @params $where //传入修改条件
     * @params $arr //传入修改数据
     */
    public static function update( $where , $arr ) {
        $OrderMiniNotifyLogReturn = new OrderMiniNotifyLogReturn;
        $OrderMiniNotifyLogReturn = $OrderMiniNotifyLogReturn->where($where)->first();
        $OrderMiniNotifyLogReturn->response_time = $arr['response_time'];
        $OrderMiniNotifyLogReturn->data_text_response = $arr['data_text_response'];
        $b = $OrderMiniNotifyLogReturn->update();
        return $b;
    }

    public static function getInfo( $where = [] ) {
        $OrderMiniNotifyLogReturn = new OrderMiniNotifyLogReturn();
        $result =  $OrderMiniNotifyLogReturn->where($where)->first();
        if (!$result) {
            get_instance()->setCode(\App\Lib\ApiStatus::CODE_35002)->setMsg('芝麻小程序查询转发日志失败');
            return [];
        }
        $Info = $result->toArray();
        return $Info;
    }

}