<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\MiniOrderRentNotify;

/**
 * 小程序确认订单回调 记录表
 * Class MiniOrderRentNotifyRepository
 * Author zhangjinhui
 * @package App\Order\Modules\Repository
 */
class MiniOrderRentNotifyRepository
{
    public function __construct(){}

    /**
     * 添加记录信息
     * @param $data
     * @return $last_id
     */
    public static function add($data){
        $info =MiniOrderRentNotify::create($data);
        return $info->getQueueableId();
    }

    /**
     * 根据订单编号获取单条订单信息
     * @param string $orderNo 订单编号
     * @return array $orderInfo 订单基础信息|空<br/>
     * $orderInfo = [<br/>
     *		'id' => '',//订单自增id<br/>
     *		'order_no' => '',//业务平台订单号<br/>
     *		'zm_order_no' => '',//芝麻订单号<br/>
     *		'transaction_id' => '',//芝麻请求流水号<br/>
     *		'cert_no' => '',//证件号<br/>
     *		'mobile' => '',//手机号<br/>
     *		'house' => '',//住宅地址<br/>
     *		'zm_grade' => '',//级别<br/>
     *		'credit_amount' => '',//信用权益金额<br/>
     *		'zm_score' => '',//
     *		'zm_risk' => '',//芝麻风控产品集联合结果<br/>
     *		'zm_face' => '',//人脸核身结果<br/>
     *		'user_id' => '',//支付宝 userid<br/>
     *		'channel_id' => '',//渠道来源<br/>
     *		'create_time' => '',//创建时间<br/>
     * ]
     */
    public static function getMiniOrderRentNotify( $orderNo ) {
        $MiniOrder = new MiniOrderRentNotify();
        $result =  $MiniOrder->where(['order_no'=> $orderNo])->first();
        if (!$result) {
            get_instance()->setCode(\App\Lib\ApiStatus::CODE_35002)->setMsg('芝麻小程序订单信息获取失败');
            return [];
        }
        $miniOrderInfo = $result->toArray();
//		var_dump($miniOrderInfo);exit;
//		$miniOrderInfo['create_time'] = date('Y-m-d H:i:s',$miniOrderInfo['create_time']);
        return $miniOrderInfo;
    }
}